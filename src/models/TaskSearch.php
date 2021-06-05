<?php

namespace xlerr\task\models;

use Carbon\Carbon;
use yii\data\ActiveDataProvider;
use yii\data\BaseDataProvider;

/**
 * Class TaskSearch
 *
 * @package common\models
 */
class TaskSearch extends Task
{
    public $startTime;
    public $endTime;

    public function formName(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['startTime', 'endTime'], 'default', 'value' => Carbon::now()->toDateString()],
            [['task_id', 'task_status', 'task_key', 'task_request_data', 'task_type', 'task_response_data'], 'safe'],
        ];
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search(array $params): BaseDataProvider
    {
        $query = self::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'  => [
                'attributes'   => ['task_create_at'],
                'defaultOrder' => ['task_create_at' => SORT_DESC],
            ],
        ]);

        $this->load($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->where([
            'and',
            ['>=', 'task_create_at', $this->startTime],
            ['<', 'task_create_at', Carbon::parse($this->endTime)->addDay()->toDateString()],
        ])->andFilterWhere([
            'task_id'     => $this->task_id,
            'task_status' => $this->task_status,
            'task_key'    => $this->task_key,
            'task_type'   => $this->task_type,
        ])->andFilterWhere([
            'and',
            ['like', 'task_request_data', $this->task_request_data],
            ['like', 'task_response_data', $this->task_response_data],
        ]);

        return $dataProvider;
    }
}
