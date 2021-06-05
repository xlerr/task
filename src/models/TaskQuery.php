<?php

namespace xlerr\task\models;

use Carbon\Carbon;
use yii\db\ActiveQuery;

class TaskQuery extends ActiveQuery
{
    /**
     * @return $this
     */
    public function close(): self
    {
        $this->andWhere([
            'task_status' => Task::STATUS_CLOSE,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function error(): self
    {
        $this->andWhere([
            'task_status' => Task::STATUS_ERROR,
        ]);

        return $this;
    }

    /**
     * @param string $time default 1 days ago
     *
     * @return $this
     */
    public function past(string $time = '1 days ago'): self
    {
        $this->andWhere(['<', 'task_update_at', Carbon::parse($time)->toDateTimeString()]);

        return $this;
    }
}
