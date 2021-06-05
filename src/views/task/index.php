<?php

use common\widgets\DatePicker;
use xlerr\task\models\Task;
use xlerr\task\models\TaskSearch;
use xlerr\common\widgets\ActiveForm;
use xlerr\common\widgets\GridView;
use xlerr\common\widgets\Select2;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\web\View;

/* @var $this View */
/* @var $model TaskSearch */
/* @var $dataProvider ActiveDataProvider */

Yii::$app->getFormatter()->nullDisplay = '';

$this->title = '任务管理';

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="box box-default">
    <div class="box-header with-border">
        <h3 class="box-title">搜索</h3>
    </div>

    <div class="box-body">
        <?php $form = ActiveForm::begin([
            'action'        => [''],
            'method'        => 'get',
            'type'          => ActiveForm::TYPE_INLINE,
            'waitingPrompt' => ActiveForm::WAITING_PROMPT_SEARCH,
            'fieldConfig'   => [
                'options'      => [
                    'class' => 'form-group',
                    'style' => 'width: 154px',
                ],
                'inputOptions' => [
                    'class' => 'form-control',
                    'style' => 'width: 100%',
                ],
            ],
        ]) ?>

        <?= $form->field($model, 'startTime')->widget(DatePicker::class) ?>

        <?= $form->field($model, 'endTime')->widget(DatePicker::class) ?>

        <?= $form->field($model, 'task_id') ?>

        <?= $form->field($model, 'task_type') ?>

        <div style="height: 10px"></div>

        <?= $form->field($model, 'task_key') ?>

        <?= $form->field($model, 'task_request_data') ?>

        <?= $form->field($model, 'task_response_data') ?>

        <?= $form->field($model, 'task_status')->widget(Select2::class, [
            'data'          => Task::$TASK_STATUS,
            'hideSearch'    => true,
            'pluginOptions' => [
                'allowClear' => true,
            ],
            'options'       => [
                'prompt' => $model->getAttributeLabel('task_status'),
            ],
        ]) ?>

        <?= Html::submitButton('<i class="fa fa-search"></i> 搜索', [
            'class' => 'btn btn-primary',
            'style' => 'width: 100px',
        ]) ?>

        <?= Html::a('重置搜索条件', ['index'], [
            'class' => 'btn btn-default',
        ]) ?>

        <?php ActiveForm::end() ?>
    </div>
</div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns'      => [
        [
            'class'          => 'yii\grid\ActionColumn',
            'template'       => '{delete} {update}',
            'visibleButtons' => [
                'update' => function (Task $model) {
                    return !$model->task_is_synchronization;
                },
            ],
        ],
        'task_id',
        'task_type',
        'task_key',
        'task_from_system',
        [
            'label'  => '任务内容',
            'format' => 'raw',
            'value'  => function (Task $searchModel) {
                $data = json_decode($searchModel->task_request_data, true);
                if ($data) {
                    return Html::tag('span', StringHelper::truncate($searchModel->task_request_data, 30), [
                        'title' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ]);
                }

                return $searchModel->task_request_data;
            },
        ],
        [
            'label'     => '任务结果',
            'attribute' => 'task_response_data',
            'value'     => function ($searchModel) {
                if (strlen($searchModel->task_response_data) > 100) {
                    return substr($searchModel->task_response_data, 0, 100) . '...';
                } else {
                    return $searchModel->task_response_data;
                }
            },
        ],
        [
            'label'  => '备注',
            'filter' => 'task_memo',
            'value'  => function ($searchModel) {
                if (strlen($searchModel->task_memo) > 18) {
                    return substr($searchModel->task_memo, 0, 18) . '...';
                } else {
                    return $searchModel->task_memo;
                }
            },
        ],
        'task_is_synchronization:boolean',
        'task_priority',
        [
            'attribute' => 'task_status',
            'format'    => ['in', Task::$TASK_STATUS],
        ],
        'task_next_run_date',
        'task_retry_times',
        'task_create_at',
        'task_update_at',
    ],
]) ?>
