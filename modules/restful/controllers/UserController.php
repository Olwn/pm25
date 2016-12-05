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
    		'status' => -1,
    		'access_token' => -1,
            'userid' => 0,
            'message' => ''
    		);

    	$paras = Yii::$app->request->post();
        //$query->
        if(!isset($paras['password'], $paras['name'], $paras['email'], $paras['firstname'], $paras['lastname'], $paras['sex'], $paras['phone'], $paras['code']))
        {
            $result['message'] = 'lack of parameters';
            return $result;
        }
        if(!$this->checkInvitation($paras['code']))
        {
            $result['message'] = 'wrong invitation code';
            $result['status'] = '2';
            return $result;
        }
        $user = new \app\models\User();
    	$user->password = md5($paras['password'], false);
    	$user->name = $paras['name'];
    	$user->email = $paras['email'];
    	$user->firstname = $paras['firstname'];
    	$user->lastname = $paras['lastname'];
    	$user->sex = $paras['sex'];
    	$user->phone = $paras['phone'];
		$user->access_token = $user->password;

    	if ($user->validate()) 
    	{
            $user->save();
    		$result['access_token'] = $user->access_token;
    		$result['status'] = 1;
            $result['userid'] = $user->id;           
            $result['message'] = 'registered succesfully';
    	}
        else
        {
            $result['message'] = 'registered failed';
            $result['status'] = 0;   
        }
    	return $result;
    }

    public function checkInvitation($code)
    {
        $query = (new\yii\db\Query())
                ->select('*')
                ->from('code');
        $items = $query->all();
        foreach ($items as $item) {
            if(strval($code) == strval($item['code']))
                return true;

        return false;
        }
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
            'name' => '',
            'firstname' => '',
            'lastname' => '',
            'sex' => 0,
            'email' => '',
            'phone' => ''
            );
        $paras = Yii::$app->request->post();
        $user = \app\models\User::find()->where(['name' => $paras['name']])->one();
        if(is_null($user))
        {
            $result['status'] = -1;
            return $result;
        }
        if(md5($paras['password']) == $user->password)
        {
            $result['status'] = 1;
			$token = bin2hex(openssl_random_pseudo_bytes(16));
			$user->old_token = $user->access_token;
			$user->access_token = $token;
			$user->last_login = date('Y-m-d H:i:s', time());
			$user->save();
            $result['access_token'] = $token;
            $result['userid'] = $user->id;
            $result['name'] = $user->name;
            $result['firstname'] = $user->firstname;
            $result['lastname'] = $user->lastname;
            $result['sex'] = $user->sex;
            $result['email'] = $user->email;
            $result['phone'] = $user->phone;
        }
        else
        {
            $result['status'] = 0;
			$result['info']=md5($paras['password']);

        }
        return $result;

    }
    public function actionFindPassword()
    {
        $result = array(
            'status' => -1,
            );
        $paras = Yii::$app->request->get();
        if(!isset($paras['name']))
            return $result;

        $name = $paras['name'];
        $user = \app\models\User::find()->where(['name' => $paras['name']])->one();
        if($user == NULL)
        {
            $result['status'] = 0;
            return $result;
        }

        $mail= Yii::$app->mailer->compose();   
        $mail->setTo($user->email);  
        $mail->setSubject("Reset Password");  
        //$mail->setTextBody('zheshisha ');   //发布纯文字文本
        $mail->setHtmlBody("Please click the link to reset your password"
                            . "<br>" . 'http://ilab.tongji.edu.cn/pm25/web/site/reset'. '?name=' . $user->name . '&amp;' . 'access_token=' . $user->access_token);    //发布可以带html标签的文本
        if($mail->send())  
            $result['status'] = 1;
        else  
            $result['status'] = 0;
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

    public function actionUpdatePassword()
    {
        $result = array(
            'status' => -1,
            'access_token' => ''
            );
        $paras = Yii::$app->request->post();
        $token = md5($paras['password'], false);
        $user = \app\models\User::find()->where(['name' => $paras['name']])->one();
        if($user != NULL && $user->access_token == $paras['access_token'])
        {
            $user->access_token = $token;
            $result['status'] = 1;
            $result['access_token'] = $token;
            $user->update();
        }
        return $result;

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
