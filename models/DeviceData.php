<?php

namespace app\models;

use Yii;

class DeviceData extends \yii\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'data_device';
	}

	public function rules()
	{
		return [
		[['devid', 'pm25'], 'required']
		];
	}
}