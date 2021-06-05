<?php

use xlerr\task\models\Task;
use kartik\widgets\DateTimePicker;
use xlerr\CodeEditor\CodeEditor;
use xlerr\common\widgets\ActiveForm;
use xlerr\common\widgets\Select2;
use yii\base\InvalidParamException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\View;

/* @var $this View */
/* @var $model Task */

/**
 * @param $data
 *
 * @return string
 */
function parseJson($data)
{
    if ($data === null || $data === '') {
        return null;
    }
    try {
        $data = Json::decode($data);
        $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (InvalidParamException $e) {
        // no body
    }

    return $data;
}

$model->task_request_data  = parseJson($model->task_request_data);
$model->task_response_data = parseJson($model->task_response_data);

?>

<?php $form = ActiveForm::begin(); ?>

<div class="box-body">
    <div class="row">
        <div class="col-md-2">
            <?= $form->field($model, 'task_id')->textInput(['disabled' => true]) ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'task_from_system')->textInput() ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'task_type')->textInput() ?>
        </div>
        <div class="col-md-3">
            <?= $form->field($model, 'task_key')->textInput() ?>
        </div>
        <div class="col-md-2">
            <?= $form->field($model, 'task_is_synchronization')->widget(Select2::class, [
                'data'       => [
                    '1' => '是',
                    '0' => '否',
                ],
                'hideSearch' => true,
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'task_request_data')->widget(CodeEditor::class, [
                'clientOptions' => [
                    'mode'     => CodeEditor::MODE_JSON,
                    'minLines' => 20,
                    'maxLines' => 40,
                ],
            ]) ?>

        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'task_response_data')->widget(CodeEditor::class, [
                'clientOptions' => [
                    'mode'     => CodeEditor::MODE_JSON,
                    'minLines' => 20,
                    'maxLines' => 40,
                ],
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'task_status')->widget(Select2::class, [
                        'data'       => Task::$TASK_STATUS,
                        'hideSearch' => true,
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'task_next_run_date')->widget(DateTimePicker::class, [
                        'type'          => DateTimePicker::TYPE_INPUT,
                        'pluginOptions' => [
                            'minView'        => 'month',
                            'todayBtn'       => true,
                            'todayHighlight' => true,
                            'autoclose'      => true,
                            'format'         => 'yyyy-mm-dd',
                        ],
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'task_next_run_time')->textInput() ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'task_retry_times')->textInput() ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'task_memo')->textarea([
                'rows' => 2,
            ]) ?>
        </div>
    </div>
</div>

<div class="box-footer">
    <?= Html::submitButton('保存', ['class' => 'btn btn-primary']) ?>
    <?= Html::a('返回', ['index'], ['class' => 'btn btn-default']) ?>
</div>

<?php ActiveForm::end(); ?>
