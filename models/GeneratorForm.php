<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\di\Container;
use garyjl\simplehtmldom\SimpleHtmlDom;

/**
 * Обработка действий на форме генератора views\ex\generator.php
 */
class GeneratorForm extends Model {

    /**
     * Содержит текущее действия нажатой кнопки
     * @var string
     */
    public $create;
    /**
     * Адрес базовой страницы с категориями
     * @var string
     */
    private $urlCategory = "www.ex.ua";

    /**
     * Правила валидации входящих данных
     * @return array the validation rules.
     */
    public function rules() {
        return [
            [['create'], 'required', 'on' => 'generator'],
        ];
    }
    /**
     * Точка входа для действия generate, контролера ExController
     * @return boolean Статус
     */
    public function generate() {
        if ($this->validate()) {
            return $this->buttonAction($this->create);
        } else {
            return false;
        }
    }

   /**
    * События кнопок на форме views\ex\generator.php
    * @param string $action Действия
    * @return boolean Статус операции
    */
    private function buttonAction($action) {
        switch ($action) {
            case 'category':
                return $this->buttonCategory();
            case 'items':
                return $this->buttonItems();
            default:
                return false;
        }
    }
    /**
     * Обработка нажатия на кнопку получения категорий
     * @return boolean Статус операции
     */
    private function buttonCategory() {
        $category = $this->getCategory();
        return $this->seveToDb('category', $category);
    }
    /**
     * Получения категорий
     * Парсим соответствующую страницу и извлекаем данные
     * @return array Масив с данными категорий
     */
    private function getCategory() {
        $category = [];
        //set_error_handler(create_function('$c, $m, $f, $l', 'return true;'), -1);
        set_error_handler(
            create_function(
                '$c, $m, $f, $l',
                'throw new ErrorException($errstr, $errno, 0, $errfile, $errline);'
            ),-1
        );
        try {
            $cache = $this->cacheFileInit();
            $content = $cache->get($this->urlCategory);
            if ($content === false) {
                $content = Yii::$app->curl->get('http://' . $this->urlCategory);
                $cache->set($this->urlCategory, $content, 0);
            }
            $html = SimpleHtmlDom::str_get_html($content);
            $a = $html->find('td[class=menu_text]', 0)->find('a');
            $ignore = ['/', 'https://mail.ex.ua/', '/ru/about', '/search'];
            foreach ($a as $element) {
                if (!in_array($element->href, $ignore)) {
                    $category[] = ['url' => $element->href, 'text' => $element->plaintext];
                }
            }
        } catch (ErrorException $e) {}
        restore_error_handler();
        return $category;
    }

     /**
     * Обработка нажатия на кнопку получения списка подкатегорий из текущей категорий
     * @return boolean Статус операции
     */
    private function buttonItems() {
        return true;
    }

    /**
     * Конфигурация и инициализация кеширования файлов
     * @return yii\caching\FileCache
     */
    private function cacheFileInit() {
        $container = new Container;
        $container->set('cache', [
            'class' => 'yii\caching\FileCache',
            'cacheFileSuffix' => '.dat',
        ]);
        return $container->get('cache');
    }

    /**
     * Сохранения собраных даных в таблицу базы данных
     * @param string $table Таблица базы данных
     * @param string $data Даные для наполнения таблицы
     * @return boolean Статус операции
     */
    private function seveToDb($table, $data) {
        return false;
    }

}
