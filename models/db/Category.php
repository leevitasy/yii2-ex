<?php namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * Данные таблицы category
 */
class Category extends ActiveRecord
{

    public static function tableName()
    {
        return 'category';
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
            'id' => 'Category ID',
            'url' => 'Ссыддка категории',
            'text' => 'Название категории'
        ];
    }

}