<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\di\Container;
use yii\db\Schema;
//use yii\base\ErrorException;
use garyjl\simplehtmldom\SimpleHtmlDom;
use app\models\db\Category;

/**
 * Обработка действий на форме генератора views\ex\generator.php
 */
class GeneratorForm extends Model {

    /**
     * Обновить документы в кеше новыми записями
     * @var bollean
     */
    private $updateCache = false;
    /**
     * Содержит текущее действия нажатой кнопки
     * @var string
     */
    public $create;
    /**
     * Идентификатор категории
     * @var intiger
     */
    public $category_id = 1;
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
            [['create','category_id'], 'required', 'on' => 'generator'],
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
        $url = 'http://' . $this->urlCategory;
        $category = $this->getCategory($url);
        return $this->seveToDb('category', $category);
    }
    /**
     * Получения категорий
     * Парсим соответствующую страницу и извлекаем данные
     * @return array Масив с данными категорий
     */
    private function getCategory($url) {
        $category = [];
        set_error_handler(create_function('$c, $m, $f, $l', 'return false;'), -1);
        //set_error_handler(
        //    create_function(
        //        '$c, $m, $f, $l',
        //        'throw new ErrorException($errstr, $errno, 0, $errfile, $errline);'
        //    ),-1
        //);
        try {
            $content = $this->getContent($url);
            if(!empty($content)){
                $html = SimpleHtmlDom::str_get_html($content);
                $a = $html->find('td[class=menu_text]', 0)->find('a');
                $ignore = ['/', 'https://mail.ex.ua/', '/ru/about', '/search'];
                foreach ($a as $element) {
                    if (!in_array($element->href, $ignore)) {
                        $category[] = ['url' => $element->href, 'text' => $element->plaintext];
                    }
                }
            }
        } catch (Exception $e) {}
        restore_error_handler();
        return $category;
    }
    /**
     * Получения подкатегорий
     * Парсим соответствующую страницу и извлекаем данные
     * @return array Массив с данными подкатегорий
     */
    private function getItems($url) {
        $items = [];
        set_error_handler(create_function('$c, $m, $f, $l', 'return false;'), -1);
        try {
            $content = $this->getContent($url);
            if(!empty($content)){
                $html = SimpleHtmlDom::str_get_html($content);
                $td = $html->find('table[class=include_0]', 0)->find('tr[!id] td[!colspan]');
                foreach ($td as $element){
                    $a1 = $element->find('a', 0);
                    $a2 = $element->find('a', 1);
                    list($atext,$articles) = explode(': ',$a2->plaintext);
                    unset($atext);
                    list($url,$revision) = explode('?r=',$a1->href);
                    $items[] = [
                        'category' => $this->category_id, 'url' => $url,
                        'text' => $a1->plaintext, 'revision' => $revision,
                        'articles' => $articles
                    ];
                }
            }
        } catch (Exception $e) {}
        restore_error_handler();
        return $items;
    }

    /**
     * Получаем страничку по сcылке
     * @param string $url Cсылка на странику
     * @return string Html страничка
     */
    private function getContent($url) {
        $cache = $this->cacheFileInit();
        $content = (($this->updateCache === true) ? false : $cache->get($url));
        if ($content === false) {
            $content = \Yii::$app->curl->get($url);
            if (!empty($content)) {
                $cache->set($url, $content, 0);
                return $content;
            }
        }
        return $content;
    }

    /**
     * Обработка нажатия на кнопку получения списка подкатегорий из текущей категорий
     * @return boolean Статус операции
     */
    private function buttonItems() {
        $category = Category::find()->where(['id' => $this->category_id])->one();
        $url = 'http://' . $this->urlCategory . $category->url;
        $items = $this->getItems($url);
        return $this->seveToDb('item', $items);
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
        if ($this->checkTableStructure($table) === true) {
            $rows = [];
            $columns = array_keys($data[0]);
            foreach ($data as $value) {
                $rows[] = array_values($value);
            }
            $db = \Yii::$app->db;
            $command = $db->createCommand();
            switch ($table) {
                case 'category':
                    $command->truncateTable($table)->execute();
                    break;
                case 'item':
                    $command->delete($table, 'category = ' . $this->category_id)->execute();
                    break;
            }
            return $command->batchInsert($table, $columns, $rows)->execute();
        }
        return false;
    }

    public function isCategory(){
        $db = \Yii::$app->db;
        return ($db->schema->getTableSchema('category',true) !== null);
    }

    public function isItem(){
        $db = \Yii::$app->db;
        return ($db->schema->getTableSchema('item',true) !== null);
    }
    /**
     * Проверка необходимых таблиц в базе данных
     * Создание таблицы при необходимых
     * @param string $table
     */
    private function checkTableStructure($table){
        $db = \Yii::$app->db;
        $status = true;
        if ($db->schema->getTableSchema($table,true) === null){
            switch($table){
               case 'category':
                   $this->createShemaCategory();
                   if($db->schema->getTableSchema($table,true) === null){
                       $status = false;
                   }
               break;
               case 'item':
                   $this->createShemaItem();
                   if($db->schema->getTableSchema($table,true) === null){
                       $status = false;
                   }
               break;
            }
        }
        return $status;
    }
    /**
     * Создание структуры таблицы category
     */
    private function createShemaCategory(){
        $db = \Yii::$app->db;
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=MyISAM';
        $command = $db->createCommand();
        //create table category
        $command->createTable('category', [
            'id' => Schema::TYPE_PK,
            'url' => 'VARCHAR(50) NOT NULL',
            'text' => 'VARCHAR (50) NOT NULL',
            'ts' => 'TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ], $tableOptions)->execute();
    }

    /**
     * Создание структуры таблицы item
     */
    private function createShemaItem(){
        $db = \Yii::$app->db;
        $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=MyISAM';
        $command = $db->createCommand();
        //create table item
        $command->createTable('item', [
            'id' => Schema::TYPE_PK,
            'category' => 'INT(2) NOT NULL DEFAULT \'0\'',
            'url' => 'VARCHAR(50) NOT NULL',
            'text' => 'VARCHAR(50) NOT NULL',
            'revision' => 'INT(2) NOT NULL DEFAULT \'0\'',
            'articles' => 'INT(2) NOT NULL DEFAULT \'0\'',
            'ts' => 'TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP'
        ], $tableOptions)->execute();
    }
}
