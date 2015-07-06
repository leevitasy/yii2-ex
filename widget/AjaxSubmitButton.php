<?php namespace app\widget;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

class AjaxSubmitButton extends Widget {

    public $ajaxOptions = [];

    /**
     * @var array the HTML attributes for the widget container tag.
     */
    public $options = [];

    /**
     * @var string the tag to use to render the button
     */
    public $tagName = 'button';

    /**
     * @var string the button label
     */
    public $label = 'Button';

    /**
     * @var boolean whether the label should be HTML-encoded.
     */
    public $encodeLabel = true;
    public $clickedButtonVarName = '_clickedButton';

    /**
     * Initializes the widget.
     */
    public function init() {
        parent::init();

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        if(isset($this->options['class'])){
            $this->options['class'] .= ' ajax-button';
        }else{
            $this->options['class'] = 'ajax-button';
        }
    }

    public function run() {
        parent::run();

        echo Html::tag($this->tagName, $this->encodeLabel ? Html::encode($this->label) : $this->label, $this->options);

        if (!empty($this->ajaxOptions)) {
            $this->registerAjaxScript();
        }
    }

    protected function registerAjaxScript() {
        $view = $this->getView();

        if (!isset($this->ajaxOptions['type'])) {
            $this->ajaxOptions['type'] = new JsExpression('$(this).parents("form").attr("method")');
        }

        if (!isset($this->ajaxOptions['url'])) {
            $this->ajaxOptions['url'] = new JsExpression('$(this).parents("form").attr("action")');
        }

        if (!isset($this->ajaxOptions['data']) && isset($this->ajaxOptions['type']))
            $this->ajaxOptions['data'] = new JsExpression('$.param(formData)');

        $this->ajaxOptions = Json::encode($this->ajaxOptions);

        $view->registerJs("$('#" . $this->options['id'] . "').on(\"buttonSubmitAjax\", function(event) {
            var me = $(event.target),
                action = false,
                form = me.parents('form'),
                formData = form.serializeArray(),
                action = $('input[type=\"hidden\"][name=\"' + me.attr('name') + '\"]', form);

            if(action.length === 0){
                if(me.attr('name')) {
                   formData.push({
                     name: me.attr('name'),
                     value: me.val()
                   });
                }
            }
            $.ajax(" . $this->ajaxOptions . ");
            return false;
        });");

        $view->registerJs("$('#" . $this->options['id'] . "').click(function() {
            " . (null !== $this->clickedButtonVarName ? "var {$this->clickedButtonVarName} = this;" : "") . "
                var confirm = $(_clickedButton).data('confirm'),
                    fnAction = function(){
                        $(_clickedButton).parents('form').data().yiiActiveForm.submitting = true;
                        $(_clickedButton).parents('form').yiiActiveForm('validate');
                    };
                if(confirm && bootbox){
                    bootbox.confirm(confirm, function (confirmed) {
                        if (confirmed) {
                            !fnAction || fnAction();
                        }
                    });
                }else{
                    !fnAction || fnAction();
                }
                return false;
        });");
    }

}
