<?php

use xlerr\task\models\Task;
use yii\web\View;

/* @var $this View */
/* @var $model Task */

$this->title = '创建任务';
$this->params['breadcrumbs'][] = ['label' => 'Tasks', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="task-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
