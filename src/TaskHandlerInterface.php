<?php

namespace xlerr\task;

use xlerr\task\models\Task;

interface TaskHandlerInterface
{
    public function process();

    public function isNeedRedo(Task $task, $data): bool;

    public function isNeedSuspend(): bool;

    public function beforeProcess(): bool;
}
