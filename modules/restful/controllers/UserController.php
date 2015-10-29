<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class UserController extends \yii\rest\ActiveController
{
	public $modelClass = 'app\models\User';

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogon()
    {
    	$result = array(
    		'status' => 'failed',
    		'access_token' => -1,
            'userid' => 0,
    		);

    	$paras = Yii::$app->request->post();
        //$query->

        $user = new \app\models\User();
    	$user->access_token = md5($paras['password'], false);
    	$user->name = $paras['name'];
    	$user->email = $paras['email'];
    	$user->firstname = $paras['firstname'];
    	$user->lastname = $paras['lastname'];
    	$user->sex = $paras['sex'];
    	$user->phone = $paras['phone'];

    	if ($user->validate()) 
    	{
            $user->save();
    		$result['access_token'] = $user->access_token;
    		$result['status'] = 1;
            $result['userid'] = $user->id;            
    	}
        else
        {
            $result['status'] = 0;   
        }
    	return $result;
    }
    public function actionTest()
    {
        return 'test';
    }
    public function actionLogin()
    {

        $result = array(
            'status' => '-1',
            'access_token' => -1,
            'userid' => 0,
            );
        $paras = Yii::$app->request->post();
        $user = \app\models\User::find()->where(['name' => $paras['name']])->one();
        if(is_null($user))
        {
            $result['status'] = -1;
            return $result;
        }
        if(md5($paras['password']) == $user->access_token)
        {
            $result['status'] = 1;
            $result['access_token'] = $user->access_token;
            $result['userid'] = $user->id;
        }
        else
        {
            $result['status'] = 0;

        }
        return $result;

    }

    public function actionGettoken()
    {
    	$paras = Yii::$app->request->post();
    	$token = md5($paras['password'], false);
    	$user = \app\models\User::find()->where(['name' => $paras['name']])->one();
    	/*
    	$result = array()
    	{

    	};*/
    	if($user != NULL && $user->access_token == $token)
    	{
    		return array(
    			'token' => $token,
    		);
    	}
    	return "error";

    }

    public function actions()
    {
        $actions = parent::actions();

        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];

        return $actions;
    }

    public function prepareDataProvider()
    {
        $conditions = Yii::$app->request->get();
        //echo $conditions['time_point'];
		if (!(isset($conditions['devid']) && isset($conditions['time_point'])))
		{
			return null;
		}
        return $this->queryWithConditions($conditions);
    }
}