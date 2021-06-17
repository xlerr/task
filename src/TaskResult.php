<?php

namespace xlerr\task;

use yii\base\BaseObject;

class TaskResult extends BaseObject
{
    /** @var int */
    public int $code;

    /** @var mixed */
    public $message;

    /** @var mixed */
    public $data;

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'code'    => $this->code,
            'message' => $this->message,
            'data'    => $this->data,
        ];
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function success($data = null): self
    {
        return new static([
            'code'    => 0,
            'message' => 'ok',
            'data'    => $data,
        ]);
    }

    public static function failure(string $msg): self
    {
        return new static([
            'code'    => 1,
            'message' => $msg,
        ]);
    }
}
