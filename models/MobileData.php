<?php

namespace app\models;

use Yii;

class MobileData extends \yii\db\ActiveRecord
{
	public static function tableName()
    {
        return 'data_mobile';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userid', 'time_point', 'longitude', 'latitude', 'outdoor', 'status', 'steps', 'avg_rate', 'ventilation_volume', 'pm25', 'source'], 'required'],
            [['userid', 'longitude', 'latitude', 'outdoor', 'status', 'steps', 'avg_rate', 'ventilation_volume', 'pm25', 'source'], 'string'],
        ];
    }

}