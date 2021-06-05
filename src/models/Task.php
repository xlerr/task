<?php

namespace xlerr\task\models;

use Carbon\Carbon;
use Exception;
use Throwable;
use yii\db\ActiveRecord;
use yii\db\Connection;

/**
 * This is the model class for table "task".
 *
 * @property int    $task_id
 * @property string $task_type
 * @property string $task_key
 * @property string $task_from_system
 * @property string $task_request_data
 * @property string $task_response_data
 * @property string $task_memo
 * @property bool   $task_is_synchronization
 * @property string $task_status
 * @property string $task_next_run_date
 * @property int    $task_retry_times
 * @property int    $task_suspend_times
 * @property string $task_create_at
 * @property string $task_update_at
 * @property int    $task_priority
 */
class Task extends ActiveRecord
{
    public const STATUS_OPEN       = 'open';
    public const STATUS_RUNNING    = 'running';
    public const STATUS_CLOSE      = 'close';
    public const STATUS_TERMINATED = 'terminated';
    public const STATUS_ERROR      = 'error';

    public const PRIORITY_1 = 1; //分片
    public const PRIORITY_2 = 2; //分片
    public const PRIORITY_3 = 3; //单线程
    public const PRIORITY_4 = 4; //分配
    public const PRIORITY_5 = 5; //长时间

    public const MAX_RETRY_TIMES = 10;

    public const ERROR_CODE              = 'error_code';
    public const ERROR_CODE_SYSTEM_ERROR = '1';

    public static array $TASK_STATUS = [
        'open'       => '待处理',
        'running'    => '处理中',
        'close'      => '成功',
        'terminated' => '终止',
        'error'      => '处理失败',
    ];

    public ?string $task_next_run_time = null;

    public ?object $requestData = null;

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%task}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['task_type', 'task_key', 'task_is_synchronization'], 'required'],
            [['task_request_data', 'task_response_data', 'task_memo', 'task_status'], 'string'],
            [['task_retry_times', 'task_suspend_times', 'task_priority'], 'integer'],
            [['task_next_run_date', 'task_next_run_time', 'task_create_at', 'task_update_at'], 'safe'],
            [['task_type'], 'string', 'max' => 128],
            [['task_key', 'task_from_system'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'task_id'                 => '任务编号',
            'task_type'               => '任务类型',
            'task_key'                => '任务键值',
            'task_from_system'        => '任务来源',
            'task_request_data'       => '任务内容',
            'task_response_data'      => '任务结果',
            'task_memo'               => '备注内容',
            'task_is_synchronization' => '同步',
            'task_status'             => '任务状态',
            'task_next_run_date'      => '下次运行日期',
            'task_next_run_time'      => '时间',
            'task_retry_times'        => '重试次数',
            'task_create_at'          => '创建日期',
            'task_update_at'          => '最后更新日期',
            'task_priority'           => '任务优先级',
        ];
    }

    /**
     * {@inheritdoc}
     * @return TaskQuery the active query used by this AR class.
     */
    public static function find(): TaskQuery
    {
        return new TaskQuery(get_called_class());
    }

    /**
     * 添加创建日志信息
     */
    public function setCreateLogInfo()
    {
        $this->task_create_at = Carbon::now()->toDateTimeString();
    }

    /**
     * 合成yyyy-mm-dd hh-mm-ss格式的时间。
     */
    public function combineDateTime()
    {
        $this->task_next_run_date = $this->task_next_run_date . ' ' . $this->task_next_run_time;
    }

    /**
     * 创建之前需要修改、填补的属性信息
     */
    public function setCreateInfo()
    {
        $this->setCreateLogInfo();
        $this->combineDateTime();
    }

    /**
     * 根据Date字段填充 Date和time字段
     */
    public function setDateField()
    {
        $date                     = Carbon::parse($this->task_next_run_date);
        $this->task_next_run_date = $date->toDateString();
        $this->task_next_run_time = $date->toTimeString();
    }

    /**
     * 获取前100条需要异步处理的任务.并且这些任务需要最近半个小时内处理完成
     *
     * @param int $sliceCount
     * @param int $totalSliceCount
     * @param int $priority
     *
     * @return array
     * @throws Throwable
     */
    public static function getTop100NeedProcessAsyncTask(int $sliceCount, int $totalSliceCount, int $priority): array
    {
        return self::getDb()->useMaster(function (Connection $db) use ($sliceCount, $totalSliceCount, $priority) {
            return self::find()
                ->andWhere(['>', 'task_next_run_date', Carbon::parse('120 minutes ago')->toDateTimeString()])
                ->andWhere(['<=', 'task_next_run_date', Carbon::now()->toDateTimeString()])
                ->andWhere([
                    'task_is_synchronization' => 0,
                    'task_status'             => Task::STATUS_OPEN,
                ])
                ->andWhere(sprintf("mod(task_id,%s)=%s", $totalSliceCount, $sliceCount))
                ->andWhere(['task_priority' => $priority])
                ->orderBy(['task_next_run_date' => SORT_ASC,])
                ->limit(500)
                ->select(['task_id'])
                ->column();
        });
    }

    /**
     * 获取前100条需要异步处理的任务.并且这些任务需要最近半个小时前的处理完成
     *
     * @return array
     * @throws Throwable
     */
    public static function getTop100NeedProcessOneHourBeforeAsyncTask(): array
    {
        return self::getDb()->useMaster(function (Connection $db) {
            return self::find()
                ->andWhere(['<=', 'task_next_run_date', Carbon::parse('120 minutes ago')->toDateTimeString()])
                ->andWhere([
                    'task_is_synchronization' => 0,
                    'task_status'             => [Task::STATUS_OPEN, Task::STATUS_ERROR],
                ])
                ->orderBy(['task_next_run_date' => SORT_ASC])
                ->limit(100)
                ->select(['task_id'])
                ->column($db);
        });
    }

    /**
     * 获取前100条异步处理中但是超时30分钟的任务,如果处理失败超过10次不再处理.
     *
     * @return array<int>
     * @throws Throwable
     */
    public static function getTop100OverdueAsyncTask(): array
    {
        return self::getDb()->useMaster(function (Connection $db) {
            return self::find()
                ->andWhere(['=', 'task_is_synchronization', '0'])
                ->andWhere(['<', 'task_retry_times', '10'])
                ->andWhere(['=', 'task_status', Task::STATUS_RUNNING])
                ->andWhere([
                    '<',
                    'task_update_at',
                    Carbon::parse('30 minutes ago')->toDateTimeString(),
                ])//如果30分钟都没处理完成,重新处理.
                ->orderBy(['task_id' => SORT_ASC])
                ->limit(100)
                ->select('task_id')
                ->column($db);
        });
    }

    /**
     * 处理Running 状态挂起的Task.
     *
     * @throws Exception
     */
    final public function processOverdueRunningTask()
    {
        $now                      = Carbon::now()->toDateTimeString();
        $this->task_next_run_date = $now;
        $this->task_status        = self::STATUS_OPEN;
        $this->task_memo          = vsprintf("%s\n====该进程在处理中(Running状态)遇到严重错误挂起过(%s)====", [
            $this->task_memo,
            $now,
        ]);

        if (!$this->save()) {
            throw new Exception(json_encode($this->getErrors(), JSON_UNESCAPED_UNICODE));
        }
    }
}
