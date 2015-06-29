<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\db\Category;
use app\models\db\Item;
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\GeneratorForm */

$this->title = 'Generate Action';
?>
<div class="site-generator">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php $form = ActiveForm::begin([
        'id' => 'Generator-form',
        'enableAjaxValidation' => false,
        'enableClientValidation' => false,
        'options' => ['class' => 'form-horizontal'],
        'fieldConfig' => [
            'template' => "{label}\n<div class=\"col-lg-3\">{input}</div>\n<div class=\"col-lg-8\">{error}</div>",
            'labelOptions' => ['class' => 'col-lg-1 control-label ex-generate-label'],
        ],
    ]);
    $isCategory = $model->isTableHaveData('category');
    $isItemInCategory = (!empty($model->category_id))
                      ? $model->isTableHaveData('item',['category' => $model->category_id])
                      : false;
    ?>
    <div class="form-group">
        <div class="col-lg-offset-1 col-lg-11">
            <?= Html::submitButton(($isCategory === true ? 'Обновить категории' : 'Создать категории'),
                   [
                    'class' => 'btn btn-primary', 'value' => 'category', 'name' => 'GeneratorForm[create]',
                    'data-confirm' => Yii::t('yii', 'Хотите '.(($isCategory === true ? 'обновить' : 'создать')).' категории?')
                   ]) ?>
            <br /> <br />
            <?php
               if($isCategory){
                echo $form->field($model, 'category_id')
                        ->dropDownList(
                             ArrayHelper::map(Category::find()->all(),'id','text'),
                             [
                              'prompt' => '-- Выберите из списка --',
                              'onchange' => 'this.form.submit()'
                             ]
                        )
                        ->label('Категории');
                }
            ?>
            <?= Html::submitButton(($isItemInCategory === true ? 'Обновить список подкатегорий' : 'Создать список подкатегорий'),
                  [
                   'disabled'=>!$isCategory,
                   'class' => 'btn btn-primary', 'value' => 'items', 'name' => 'GeneratorForm[create]',
                   'data-confirm' => Yii::t('yii', 'Хотите '.(($isItemInCategory === true ? 'обновить' : 'создать')).' список подкатегорий?')
                  ]) ?>
            <br /> <br />
            <?php
               if($isItemInCategory){
                echo $form->field($model, 'category_item_id')
                        ->dropDownList(
                             ArrayHelper::map(
                                     Item::find()
                                     ->where(['category' => $model->category_id])
                                     ->orderBy('id')
                                     ->all(),'id','text'
                                     ),
                             ['prompt' => '-- Выберите из списка --']
                        )
                        ->label('Подкатегории');
                }
            ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
