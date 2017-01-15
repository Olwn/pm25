<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;


class CheckTokenController extends \yii\rest\ActiveController
{
    public $modelClass = 'app\models\User';
	
    public function afterAction($action, $result)
	{
		$result = parent::afterAction($action, $result);
		$token_info = Yii::$app->getResponse()->content;
		if(!is_null($token_info))
			$result += Yii::$app->getResponse()->content;
		return $result;

	}
    public function beforeAction($action)
	{
		$request = Yii::$app->request;
		$paras = $request->isPost ? $request->post() : $request->get(); 
		//echo $paras['access_token'];
		if(!isset($paras['access_token']))
		{

        	Yii::$app->getResponse()->content = array('token_status' => -1, 'last_login' => '');
			return parent::beforeAction($action);
		}

        $user1 = \app\models\User::find()->where(['access_token' => $paras['access_token']])->one();
        $user2 = \app\models\User::find()->where(['old_token' => $paras['access_token']])->one();
		$token_status = 0;
		$last_login = null;
		if(!is_null($user1)){
			$last_login = $user1->last_login;
			$token_status = 1;
		}
		if(!is_null($user2)){
			$last_login = $user2->last_login;
			$token_status = 2;
		}
        Yii::$app->getResponse()->content = array('token_status' => $token_status, 'last_login' => $last_login);
		//Yii::$app->getResponse()->send();
		return parent::beforeAction($action);
	}

	public function actionTest()
	{
		$token_status = Yii::$app->getResponse()->content['token_status'];
		return array('status' => 'access successfully', 'token_status' => $token_status);
	}
}
