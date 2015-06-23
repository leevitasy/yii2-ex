<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\GeneratorForm */

$this->title = 'Generate Action';
?>
<div class="site-generator">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin([
        'id' => 'Generator-form',
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label'],
        ],
    ]); ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton('Создать категории', 
                   [
                    'class' => 'btn btn-primary', 'value' => 'category', 'name' => 'GeneratorForm[create]',
                    'data-confirm' => Yii::t('yii', 'Хотите создать категории?')
                   ]) ?>
            <br /> <br />
            <?= Html::submitButton('Создать список из категории', 
                  [
                   'class' => 'btn btn-primary', 'value' => 'items', 'name' => 'GeneratorForm[create]',
                   'data-confirm' => Yii::t('yii', 'Хотите создать список из категории?')
                  ]) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
