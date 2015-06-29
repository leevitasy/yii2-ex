<?php namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * Данные таблицы item
 */
class Item extends ActiveRecord
{

    public static function tableName()
    {
        return 'item';
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
            'id' => 'Item ID',
            'category' => 'Cсылка на ID категории',
            'url' => 'Ссылка подкатегории',
            'text' => 'Название подкатегории',
            'revision' => 'Текущая ревизия',
            'articles' => 'Количество статей в подкатегории',
            'ts' => 'Время последнего обновления содержимого'
        ];
    }

}