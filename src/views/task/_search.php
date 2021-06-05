<?php

use common\widgets\DatePicker;
use kartik\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\DtransactionSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">筛选</h3>
    </div>

    <div class="box-body">

        <?php $form = ActiveForm::begin([
            'action'  => [''],
            'method'  => 'get',
            'options' => ['class' => 'form-inline'],
        ]); ?>

        <?= DatePicker::widget([
            'name'  => 'date',
            'value' => Yii::$app->getRequest()->get('date'),
        ]) ?>

        <?= Html::submitButton('搜索', [
            'class' => 'btn btn-primary',
        ]) ?>

        <?= Html::a('重置搜索条件', [''], [
            'class' => 'btn btn-default',
        ]) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>


