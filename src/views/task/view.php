<?php

use xlerr\task\models\Task;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\DetailView;

/* @var $this View */
/* @var $model Task */

$this->title                   = $model->task_id;
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<p>
    <?= Html::a('修改', ['update', 'id' => $model->task_id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->task_id], [
        'class' => 'btn btn-danger',
        'data'  => [
            'confirm' => '确认删除?',
            'method'  => 'post',
        ],
    ]) ?>
    <?= Html::a('返回', ['index'], ['class' => 'btn btn-default']) ?>
</p>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">详情</h3>
    </div>

    <div class="box-body no-padding">
        <?= DetailView::widget([
            'model'      => $model,
            'attributes' => [
                'task_id',
                'task_type',
                'task_key',
                'task_from_system',
                'task_request_data:ntext',
                'task_response_data:ntext',
                'task_memo:ntext',
                'task_is_synchronization:boolean',
                'task_status',
                'task_next_run_date',
                'task_retry_times',
                'task_create_at',
                'task_update_at',
            ],
        ]) ?>

    </div>
</div>
