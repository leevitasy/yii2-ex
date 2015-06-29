<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\di\Container;
use yii\db\Schema;
use yii\db\Query;
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
    public $category_id;
    /**
     * Идентификатор подкатегории
     * @var intiger
     */
    public $category_item_id;
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
            [['create','category_id','category_item_id'], 'validateCategoryItemId', 'on' => 'generator']
        ];
    }

    public function validateCategoryItemId($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if (!empty($this->create)) {
                    if($this->create === 'items' && empty($this->category_id)){
                        $this->addError('category_id', 'Категория не выбрана.');
                    }
            }
        }
    }
    /**
     * Точка входа для действия generate, контролера ExController
     * @return boolean Статус
     */
    public function generate() {
        if ($this->validate()){
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
                $this->updateCache = true;
                return $this->buttonCategory();
            case 'items':
                $this->updateCache = true;
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
     * Сохранение/Обновление собраных даных в таблицу базы данных
     * @param string $table Таблица базы данных
     * @param string $data Даные для наполнения таблицы
     * @return boolean Статус операции
     */
    private function seveToDb($table, $data) {
        if ($this->checkTableStructure($table) === true) {
            $db = \Yii::$app->db;
            $transaction = $db->beginTransaction();
            set_error_handler(create_function('$c, $m, $f, $l', 'return false;'), -1);
            try {
                 foreach($data as $rec){
                    switch ($table) {
                        case 'category':
                            $sql = 'SELECT id from ' . $table . ' WHERE url=\'' . $rec['url'] . '\'';
                            break;
                        case 'item':
                            $sql = 'SELECT id from ' . $table . ' WHERE url=\'' . $rec['url'] . '\' AND category=' . $this->category_id;
                            break;
                    }
                    if($this->canUpdateRow($sql, $update_id)){ //update
                        $db->createCommand()->update($table, $rec, 'id='.$update_id)->execute();
                    } else { //insert
                        $db->createCommand()->insert($table, $rec)->execute();
                    }
                 }
                $transaction->commit();
                restore_error_handler();
                return true;
            }catch(Exception $e){
                $transaction->rollback();
            }
        }
        restore_error_handler();
        return false;
    }

    /**
     * Проверка возможности обновления данных
     * @param string $sql SQL-запрос проверки
     * @param intiger $update_id  ID записи что будем обновлять
     * @return boolean Статус проверки
     */
    private function canUpdateRow($sql, &$update_id) {
        $db = \Yii::$app->db;
        $command = $db->createCommand($sql);
        $reader = $command->query();
        $reader->bindColumn(1, $update_id);
        if($reader->read() !== false) return true;
        return false;
    }

    /**
     * Проверка существования таблицы
     * @param string $table Название таблицы
     * @return boolean Статус проверки
     */
    public function isTableExists($table){
        $db = \Yii::$app->db;
        return ($db->schema->getTableSchema($table,true) !== null);
    }

    /**
     * Проверка существования данных в таблице
     * @param string $table Название таблицы
     * @param array $where Условия проверки
     * @return boolean Статус проверки
     */
    public function isTableHaveData($table, $where = '1 = 1') {
        if ($this->isTableExists($table)) {
            $count = (new Query())
                    ->from($table)
                    ->where($where)
                    ->count();
            return ($count > 0);
        }
        return false;
    }

    /**
     * Проверка необходимых таблиц в базе данных
     * Создание таблицы при необходимых
     * @param string $table
     */
    private function checkTableStructure($table){
        $status = true;
        if (!$this->isTableExists($table)){
            switch($table){
               case 'category':
                   $this->createShemaCategory();
                   $status = $this->isTableExists($table);
               break;
               case 'item':
                   $this->createShemaItem();
                   $status = $this->isTableExists($table);
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
        //add index
        $command->createIndex('un_category_url', 'category', 'url', true)->execute();
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
        //add index
        $command->createIndex('in_item_category', 'item', 'category')->execute();
        $command->createIndex('un_item_category_url', 'item', ['category','url'], true)->execute();
    }
}
