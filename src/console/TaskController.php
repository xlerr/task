<?php

namespace xlerr\task\console;

use Throwable;
use xlerr\task\models\Task;
use xlerr\task\TaskHandler;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;

class TaskController extends Controller
{
    /**
     * 处理后台任务,仅用于测试,正式环境不能使用.
     *
     * @throws Throwable
     */
    public function actionProcessAll()
    {
        $this->actionProcessOne("0,1");
        $this->actionProcessTwo("0,1");
        $this->actionProcessThree("0,1");
        $this->actionProcessFour("0,1");
        $this->actionProcessFive("0,1");
        $this->actionProcessOneHourBefore();
    }

    /**
     * 处理异步后台任务1
     *
     * @param string $idSuffix
     *
     * @throws Throwable
     */
    public function actionProcessOne(string $idSuffix)
    {
        $this->processCore($idSuffix, Task::PRIORITY_1);
    }

    /**
     * 处理异步后台任务2
     *
     * @param string $idSuffix
     *
     * @throws Throwable
     */
    public function actionProcessTwo(string $idSuffix)
    {
        $this->processCore($idSuffix, Task::PRIORITY_2);
    }

    /**
     * 处理异步后台任务3
     *
     * @param string $idSuffix
     *
     * @throws Throwable
     */
    public function actionProcessThree(string $idSuffix)
    {
        $this->processCore($idSuffix, Task::PRIORITY_3);
    }

    /**
     * 处理异步后台任务3
     *
     * @param string $idSuffix
     *
     * @throws Throwable
     */
    public function actionProcessFour(string $idSuffix)
    {
        $this->processCore($idSuffix, Task::PRIORITY_4);
    }

    /**
     * 处理异步后台任务4
     *
     * @param string $idSuffix
     *
     * @throws Throwable
     */
    public function actionProcessFive(string $idSuffix)
    {
        $this->processCore($idSuffix, Task::PRIORITY_5);
    }

    /**
     * @param int $share
     * @param int $count
     * @param int $priority
     *
     * @return int
     * @throws Throwable
     */
    public function actionProcessDjob(int $share, int $count, int $priority): int
    {
        // djob的分片是以 1 开始。所以这里需要 -1 ;
        $this->processCore(sprintf('%s,%s', $share, $count), $priority);

        return ExitCode::OK;
    }

    /**
     * 异步任务核心处理
     *
     * @param string $idSuffix
     * @param int    $priority
     *
     * @return void
     * @throws Throwable
     */
    private function processCore(string $idSuffix, int $priority): void
    {
        $sliceArray = explode(',', $idSuffix);

        if (YII_ENV_PROD) {
            sleep(($priority - 1) * 5 + rand(0, 5));
        }
        $shard = $sliceArray[0];
        $count = $sliceArray[1];
        $shard = $shard % $count;

        $tasks = Task::getTop100NeedProcessAsyncTask($shard, $count, $priority);
        echo vsprintf("\nPriority#%d: %s\n", [
            $priority,
            json_encode($tasks),
        ]);
        Task::getDb()->useMaster(function (Connection $db) use ($tasks) {
            foreach ($tasks as $taskId) {
                $task = Task::find()->where(['task_id' => $taskId])->one($db);
                if ($task instanceof Task) {
                    $info = TaskHandler::execute($task);
                    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        . PHP_EOL;
                }
            }
        });
    }

    /**
     * 处理一小时前的异步任务
     *
     * @return int
     * @throws Throwable
     */
    public function actionProcessOneHourBefore(): int
    {
        $tasks = Task::getTop100NeedProcessOneHourBeforeAsyncTask();
        Task::getDb()->useMaster(function (Connection $db) use ($tasks) {
            foreach ($tasks as $taskId) {
                $task = Task::find()->where(['task_id' => $taskId])->one($db);
                if ($task instanceof Task) {
                    TaskHandler::execute($task);
                }
            }
        });

        return ExitCode::OK;
    }

    /**
     * 把Running了30分钟未处理完成的Task状态调整为Open
     *
     * @return int
     * @throws Throwable
     */
    public function actionProcessOverdueRunningTask(): int
    {
        $tasks = Task::getTop100OverdueAsyncTask();
        Task::getDb()->useMaster(function (Connection $db) use ($tasks) {
            foreach ($tasks as $taskId) {
                $task = Task::find()->where(['task_id' => $taskId])->one($db);
                if ($task instanceof Task) {
                    $task->processOverdueRunningTask();
                }
            }
        });

        return ExitCode::OK;
    }

    /**
     * 用户开发调试单个任务
     *
     * @param int $taskId 任务编号
     *
     * @return int
     */
    public function actionDevRun(int $taskId): int
    {
        if (YII_ENV_PROD) {
            echo '正式环境不允许使用' . PHP_EOL;

            return ExitCode::SOFTWARE;
        }

        $task = Task::findOne($taskId);
        if (!$task) {
            echo 'task not found' . PHP_EOL;

            return ExitCode::DATAERR;
        }

        TaskHandler::execute($task);

        return ExitCode::OK;
    }
}
