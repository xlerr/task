<?php

namespace xlerr\task;

use xlerr\task\models\Task;

class DemoTaskHandler extends TaskHandler
{
    public $name;

    /**
     * 验证传入的参数
     *
     * @return array
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 10],
        ];
    }

    public function process(Task $task)
    {
        echo $this->name . PHP_EOL;

        return TaskResult::success('执行成功');
    }
}
