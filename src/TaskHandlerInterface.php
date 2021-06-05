<?php

namespace xlerr\task;

use xlerr\task\models\Task;

interface TaskHandlerInterface
{
    public function process(Task $task);

    public function isNeedRedo(Task $task, $data): bool;

    public function isNeedSuspend(Task $task): bool;

    public function beforeProcess(Task $task): bool;
}
