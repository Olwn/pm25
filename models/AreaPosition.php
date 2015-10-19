<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "area_position".
 *
 * @property string $id
 * @property string $area
 * @property string $position_name
 * @property double $latitude
 * @property double $longtitude
 * @property string $alias
 */
class AreaPosition extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'area_position';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['area', 'position_name', 'latitude', 'longtitude'], 'required'],
            [['area', 'position_name', 'alias'], 'string'],
            [['latitude', 'longtitude'], 'number']
        ];
    }

    /**
     * @inheritdoc
     */
    /*
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'area' => 'city name',
            'position_name' => 'position name',
            'latitude' => 'Latitude',
            'longtitude' => 'Longtitude',
            'alias' => 'Alias',
        ];
    }
     */
}
