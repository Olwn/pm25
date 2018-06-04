<?php

namespace app\models;

use Yii;

class UrbanAir extends \yii\db\ActiveRecord
{
	public static function tableName()
    {
        return 'urban_air';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['time_point','city', 'longitude', 'latitude', 'pm25'], 'required'],
            [['aqi', 'no2', 'humidity', 'city', 'wind'], 'integer'],
            [['pm25', 'pm10', 'humidity', 'temperature', 'longitude', 'latitude','co','o3','so2'], 'double'],
            [['time_point'], 'string']
        ];
    }

}
