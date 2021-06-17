<?php

namespace xlerr\task;

use Carbon\Carbon;
use Exception;
use Throwable;
use xlerr\task\models\Task;
use Yii;
use yii\base\Model;
use yii\base\UserException;
use yii\db\Expression;
use yii\db\Transaction;
use yii\helpers\Json;

/**
 * Class TaskHandler
 *
 * @package common\tasks
 */
abstract class TaskHandler extends Model implements TaskHandlerInterface
{
    /** @var Task */
    protected Task $task;

    public function setTask(Task $task): void
    {
        $this->task = $task;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function beforeProcess(): bool
    {
        return true;
    }

    /**
     * @param Task $task
     * @param      $data
     *
     * @return bool
     */
    public function isNeedRedo(Task $task, $data): bool
    {
        return false;
    }

    /**
     * @param Task $task
     *
     * @return bool
     */
    public function isNeedSuspend(): bool
    {
        return false;
    }

    /**
     * 同步执行
     *
     * @param array $data
     * @param array $options
     *
     * @return array
     * @throws Throwable
     */
    final public function __invoke(array $data, array $options = []): array
    {
        return $this->invoke($data, $options);
    }

    /**
     * 同步任务,直接调用任务逻辑
     *
     * @param array $data
     * @param array $options
     *
     * @return array
     * @throws Throwable
     */
    final public function invoke(array $data, array $options = []): array
    {
        $task = self::preMake($data, $options);

        $oldTask = Task::findOne([
            'task_type'        => $task->task_type,
            'task_key'         => $task->task_key,
            'task_from_system' => $task->task_from_system,
        ]);
        if ($oldTask && empty($oldTask->task_response_data) && !$this->isNeedRedo($oldTask, $data)) {
            return Json::decode($oldTask->task_response_data);
        }

        $transaction = Task::getDb()->beginTransaction();
        try {
            $task->task_is_synchronization = true; // 强制设置为同步任务
            if (!$task->insert()) {
                throw new UserException('创建同步任务失败: ' . Json::encode($task->getErrors()));
            }
            $task->refresh();

            $log = self::execute($task);
            Yii::debug($log, 'task');

            $result = $log['result'] ?? null;
            if (!$result instanceof TaskResult) {
                throw new UserException($log['error']);
            }

            $transaction->commit();

            return $result->toArray();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 创建异步任务
     *
     * @param mixed $data
     * @param array $options
     *
     * @return Task
     * @throws Throwable
     * @example
     *     class ExampleTaskHandler extend TaskHandler {}
     *     ExampleTaskHandler::make($taskRequestData, [
     *         'task_key' => 'key',
     *         'task_next_run_date' => '2020-04-16 12:13:14'
     *     ]);
     */
    final public static function make($data, $options = []): Task
    {
        $task = self::preMake($data, $options);
        if (!$task->insert()) {
            throw new UserException(Json::encode($task->getErrors()));
        }

        return $task;
    }

    /**
     * @param mixed $data
     * @param array $options
     *
     * @return Task
     * @throws UserException
     */
    final private static function preMake($data, $options = []): Task
    {
        $class = static::class;
        if ($class === self::class) {
            throw new UserException('The task executor is not implemented');
        }

        $task = new Task([
            'task_type' => $class,
        ]);

        $handler = self::makeHandler($task);
        $handler->load($data);
        if (!$handler->validate()) {
            throw new UserException(Json::encode($handler->getErrors()) . ' for ' . $task->task_type);
        }

        $task->task_request_data       = Json::encode($data);
        $task->task_create_at          = Carbon::now()->toDateTimeString();
        $task->task_status             = Task::STATUS_OPEN;
        $task->task_from_system        = $options['task_from_system'] ?? 'BIZ';
        $task->task_next_run_date      = $options['task_next_run_date'] ?? $task->task_create_at;
        $task->task_priority           = $options['task_priority'] ?? Task::PRIORITY_3;
        $task->task_key                = $options['task_key'] ?? static::generateKey($data, $options);
        $task->task_is_synchronization = false;

        return $task;
    }

    /**
     * 生成 Key
     *
     * @param array $data
     * @param array $options
     *
     * @return string
     * @throws Exception
     */
    protected static function generateKey($data, $options = []): string
    {
        return hash('crc32', microtime() . Yii::$app->security->generateRandomKey());
    }

    /**
     * @param Task $task
     *
     * @return array
     */
    final public static function execute(Task $task): array
    {
        $logs = [
            '#'         => $task->task_id,
            'startTime' => Carbon::now()->toDateTimeLocalString('microsecond'),
        ];

        try {
            $handler = self::makeHandler($task);

            // 开始处理
            if (!$handler->startProcess()) {
                throw new Exception('start process failure');
            }

            $transaction = null;
            try {
                if ($handler->isNeedSuspend()) {
                    $handler->suspend();

                    return self::endLog($logs);
                }
                if (!$handler->beforeProcess()) {
                    throw new UserException('beforeProcess 执行失败');
                }

                $transaction = Task::getDb()->beginTransaction(Transaction::READ_COMMITTED);

                // 调用Handler 做业务处理
                $result = $handler->run();

                $logs['result'] = $result;

                // 处理成功
                $handler->processSuccess($result);

                $transaction->commit();
            } catch (Throwable $ex) {
                if ($transaction) {
                    $transaction->rollBack();
                }
                $handler->processFailed($ex);
                throw $ex;
            }
        } catch (Throwable $e) {
            Yii::error($e->__toString(), 'task');

            $logs['error'] = $e->getMessage();
            $logs['trace'] = $e->getTraceAsString();
        }

        return self::endLog($logs);
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return '';
    }

    /**
     * @return mixed
     * @throws Exception
     */
    final private function run(): TaskResult
    {
        $data = json_decode($this->task->task_request_data, true);

        $this->load($data);

        $result = $this->process();

        if ($result instanceof TaskResult) {
            return $result;
        }

        if (is_array($result)) {
            return new TaskResult($result);
        }

        if ($result === true) {
            return TaskResult::success();
        }

        if (is_string($result)) {
            return TaskResult::failure($result);
        }

        throw new Exception('未知执行结果');
    }

    /**
     * @param Task $task
     *
     * @return TaskHandler
     * @throws Exception
     */
    final private static function makeHandler(Task $task): self
    {
        $handler = $task->task_type;
        $handler = new $handler([
            'task' => $task,
        ]);
        if (!$handler instanceof self) {
            throw new Exception('Invalid task executor: ' . $task->task_type);
        }

        return $handler;
    }

    /**
     * 开始处理异步任务
     *
     * @return bool
     */
    final private function startProcess(): bool
    {
        $affectedRow = (int)Task::updateAll([
            'task_status'      => Task::STATUS_RUNNING,
            'task_retry_times' => new Expression('task_retry_times + 1'),
        ], [
            'and',
            ['=', 'task_id', $this->task->task_id],
            ['=', 'task_status', Task::STATUS_OPEN],
            ['<=', 'task_next_run_date', new Expression('date_add(now(), interval 2 minute)')],
            ['=', 'task_retry_times', $this->task->task_retry_times],
        ]);

        if ($affectedRow !== 1) {
            return false;
        }

        $this->task->task_status      = Task::STATUS_RUNNING;
        $this->task->task_retry_times += 1;

        return true;
    }

    /**
     * 挂起任务
     *
     * @return void
     * @throws Exception
     */
    final private function suspend()
    {
        $affectedRow = (int)Task::updateAll([
            'task_suspend_times' => new Expression('task_suspend_times + 1'),
            'task_retry_times'   => 0,
            'task_next_run_date' => Carbon::parse('30 minutes')->toDateTimeString(),
            'task_status'        => Task::STATUS_OPEN,
        ], [
            'task_id'     => $this->task->task_id,
            'task_status' => Task::STATUS_RUNNING,
        ]);

        if ($affectedRow !== 1) {
            throw new Exception(sprintf('Task[%d] 暂停失败, 保持running状态', $this->task->task_id));
        }
    }

    /**
     * 异步任务处理成功
     *
     * @param $result
     *
     * @throws Exception
     */
    final private function processSuccess(TaskResult $result)
    {
        $status = $this->task->task_retry_times > Task::MAX_RETRY_TIMES ? Task::STATUS_TERMINATED : Task::STATUS_OPEN;
        //Code =2 ,任务处理中,还需要等待下次处理.
        if ($result->code == 2) {
            $data = [
                'task_status'        => $status,
                'task_response_data' => $result->toString(),
                'task_next_run_date' => $this->nextRunTime($this->task->task_retry_times),
            ];
        } elseif ($result->code == 1 || $result->code == 0) {
            $data = [
                'task_status'        => Task::STATUS_CLOSE,
                'task_response_data' => $result->toString(),
            ];
        } else {
            throw new Exception(sprintf('不支持返回的Code[%s]', $result->code));
        }

        $affectedRow = (int)Task::updateAll($data, [
            'task_status' => Task::STATUS_RUNNING,
            'task_id'     => $this->task->task_id,
        ]);

        if ($affectedRow !== 1) {
            throw new Exception(sprintf('Task[%d]更新失败, 保持running状态', $this->task->task_id));
        }
    }

    /**
     * 异步任务处理失败
     *
     * @param Throwable $exception
     *
     * @return void
     * @throws Exception
     */
    final private function processFailed(Throwable $exception)
    {
        $status = $this->task->task_retry_times > Task::MAX_RETRY_TIMES ? Task::STATUS_TERMINATED : Task::STATUS_OPEN;

        $affectedRow = (int)Task::updateAll([
            'task_next_run_date' => $this->nextRunTime($this->task->task_retry_times),
            'task_status'        => $status,
            'task_memo'          => vsprintf("=== %s ===\n%s\n%s", [
                $this->task->task_retry_times,
                $exception->getMessage(),
                $exception->getTraceAsString(),
            ]),
        ], [
            'task_id'     => $this->task->task_id,
            'task_status' => Task::STATUS_RUNNING,
        ]);

        if ($affectedRow !== 1) {
            throw new Exception(vsprintf('Task[%d]更新失败, 保持running状态: %s', [
                $this->task->task_id,
                $exception->getMessage(),
            ]));
        }

        if ($this->task->task_retry_times > Task::MAX_RETRY_TIMES) {
            throw new Exception(vsprintf(
                "Task失败次数超限，task_id 为【%s】,\ntask_type 为【%s】,\n task_request_data 为【%s】,\ntask_memo 为【%s】",
                [
                    $this->task->task_id,
                    $this->task->task_type,
                    $this->task->task_request_data,
                    $this->task->task_memo,
                ]
            ));
        }
    }

    /**
     * 获取异步任务下次运行时间.
     *
     * @param int $retryTimes
     *
     * @return string
     */
    final private function nextRunTime(int $retryTimes): string
    {
        $minutes = [
                1 => 5,
                2 => 30,
                3 => 60,
                4 => 120,
            ][$retryTimes] ?? 360;

        return Carbon::now()->addMinutes($minutes)->toDateTimeString();
    }

    /**
     * @param array $info
     *
     * @return array
     */
    final private static function endLog(array $info): array
    {
        $info['endTime'] = Carbon::now()->toDateTimeLocalString('microsecond');

        return $info;
    }
}
