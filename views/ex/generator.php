<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use app\models\db\Category;
use app\models\db\Item;
use app\models\db\Articles;
use app\widget\AjaxSubmitButton;
use yii\web\JsExpression;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model app\models\GeneratorForm */

$this->title = 'Generate Action';
?>
<div class="site-generator">
    <h1><?= Html::encode($this->title) ?></h1>
    <?php
   $this->registerJs("
      $('#Generator-form').on(\"submit\", function(event) {
         var yiiActiveForm = $(event.target).data().yiiActiveForm;
         if(yiiActiveForm.submitObject.attr('class').search('ajax-button') > -1){
           if(yiiActiveForm.validated === true){
               yiiActiveForm.submitObject.trigger('buttonSubmitAjax');
           }
           return false;
         }
         return true;
       });", View::POS_READY);

      $form = ActiveForm::begin([
        'id' => 'Generator-form',
        'enableAjaxValidation' => true,
        'enableClientValidation' => false,
        'validateOnChange' => false,
        'validateOnBlur' => false,
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
    $isArticlesInItem = (!empty($model->category_item_id))
                      ? $model->isTableHaveData('articles',['item' => $model->category_item_id])
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
                             [
                                 'prompt' => '-- Выберите из списка --',
                                 'onchange' => 'this.form.submit()'
                             ]
                        )
                        ->label('Подкатегории');
                }
            ?>
            <?php AjaxSubmitButton::begin([
                'label' => ($isArticlesInItem === true ? 'Обновить список статей' : 'Создать список статей'),
                'ajaxOptions' => [
                    'type'=>'POST',
                    'url'=>Yii::$app->request->url.'/json/articles',
                    'cache' => false,
                    'processData' => false,
                    'success' => new JsExpression('
                        function(data, textStatus, jqXHR){
                            if(!data){
                               console.log("Ошибка, нет данных!!!");
                               return false;
                            }
                            if(data["success"] !== true){
                              // обрабатываем как ошибку
                              console.log("Произошла ошибка!!!");
                              return false;
                            }
                            var articles = (data["articles"]) ? data["articles"]: 0,
                                pageTotal = (data["pageTotal"]) ? data["pageTotal"]: 0,
                                page, hiddenButton = {},
                                name_hidden = me.attr("name").replace(/\[(.*)\]$/,"[" + me.attr("value") + "_page_step]"),
                                html_out = "Обработка страницы: ";
                                html_out += articles;
                                html_out += " из "+pageTotal;
                                hiddenPage = $(\'input[type="hidden"][name="\' + name_hidden + \'"]\', form);
                                hiddenButton = $(\'input[type="hidden"][name="\' + me.attr(\'name\') + \'"]\', form);
                                if (!hiddenPage.length) {
                                    // create hidden button
                                    hiddenPage = $(\'<input>\').attr({
                                        type: \'hidden\',
                                        name: name_hidden,
                                        value: 0
                                    }).appendTo(form);
                                }
                                page = hiddenPage.attr(\'value\');
                            $("#articles-progres").html(html_out);
                            if(page < pageTotal){
                                hiddenPage.attr(\'value\', ++page);
                                me.trigger(\'buttonSubmitAjax\');
                            }else{
                                hiddenPage.remove();
                                hiddenButton.remove();
                                // повторно запрашиваем статьи для отображения
                                $(\'#generatorform-category_item_id\').trigger(\'onchange\');
                            }
                    }'),
                    'error'=> new JsExpression('
                        function(jqXHR, textStatus, errorThrown){
                            console.log(["error",textStatus,errorThrown]);
                    }'),
                ],
                'options' => [
                    'id'=>'ajax-submit-articles',
                    'class' => 'btn btn-primary',
                    'type' => 'submit',
                    'disabled'=>!$isItemInCategory,
                    'value' => 'articles',
                    'name' => 'GeneratorForm[create]',
                    'data-confirm' => Yii::t('yii', 'Хотите '.(($isArticlesInItem === true ? 'обновить' : 'создать')).' список статей?')
                    ],
                ]);
                AjaxSubmitButton::end();
            ?>
            <br /> <br />
            <div id="articles-progres"></div>
            <?php
               if($isArticlesInItem){
                echo $form->field($model, 'category_articles_id')
                        ->dropDownList(
                             ArrayHelper::map(
                                     Articles::find()
                                     ->where(['item' => $model->category_item_id])
                                     ->orderBy('ts DESC')
                                     ->limit(25)
                                     ->all(),'id','text'
                                     ),
                             ['prompt' => '-- Топ 25 --']
                        )
                        ->label('Статьи');
                }
            ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
    <?php
        $this->registerJsFile(
                Yii::$app->getUrlManager()->getBaseUrl() . '/js/ajax-load-mask.js',
                ['position' => View::POS_END,'depends' => ['app\assets\AppAsset']]
        );
    ?>
</div>
