<?php

namespace app\models;

use Yii;

class NewMobileData extends \yii\db\ActiveRecord
{
	public static function tableName()
    {
        return 'data_mobile_new';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
				[['userid', 'time_point', 'longitude', 'latitude', 'outdoor', 'status', 'steps', 'ventilation_rate',
				 'ventilation_vol', 'pm25_concen','pm25_intake', 'pm25_datasource', 'APP_version'], 'required'],
        ];
    }

}
