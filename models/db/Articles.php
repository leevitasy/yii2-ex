<?php namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * Данные таблицы articles
 */
class Articles extends ActiveRecord
{

    public static function tableName()
    {
        return 'articles';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'text'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Articles ID',
            'item' => 'Cсылка на ID подкатегории',
            'url' => 'Ссылка статьи',
            'text' => 'Название статьи полное',
            'text1' => 'Название статьи 1 часть',
            'type' => 'Тип',
            'revision' => 'Текущая ревизия',
            'author' => 'Автор',
            'reviews' => 'Отзывы',
            'reviews_id' => 'ID Отзыва',
            'public_time' => 'Время публикации статьи',
            'ts' => 'Время последнего обновления содержимого'
        ];
    }

}