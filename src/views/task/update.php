<?php

use xlerr\task\models\Task;
use yii\web\View;

/* @var $this View */
/* @var $model Task */

$this->title = '修改任务: ' . $model->task_id;

$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">修改</h3>
    </div>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
