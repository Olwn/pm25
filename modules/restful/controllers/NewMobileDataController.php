<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class NewMobileDataController extends CheckTokenController
{
    public $modelClass = 'app\models\NewMobileData';

    public function actionIndex()
    {
        return $this->render('index');
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
		if (!(isset($conditions['userid']) && isset($conditions['time_point'])))
		{
			return null;
		}
        return $this->queryWithConditions($conditions);
    }

    public function actionUpload()
    {
        $result = array(
            'succeed_count' => 0,
			'message' => ''
            );
		$token_status = Yii::$app->getResponse()->content['token_status'];
		if($token_status == -1)
		{
			$result['message'] = 'no token';
			return $result; 
		}
		if($token_status == 0)
		{
			$result['message'] = 'invalid token';
			return $result; 
		}
        $data = Yii::$app->request->post();

        foreach ($data['data'] as $columns) {
            $result['succeed_count']  += DefaultController::saveModel($this->modelClass, $columns);
        }
        return $result;
    }

    public function queryWithConditions($conditions)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('data_mobile_new');
            
        if(strlen($conditions['time_point']) < 15 )
        {
            $query->where(['=', "DATE_FORMAT(time_point, '%Y-%m-%d')", $conditions['time_point']]);
        	unset($conditions['time_point']);
    	}
        

        $query->andWhere($conditions);
        $dataProvider = new ActiveDataProvider([
            /*'query' => $model::find()->where($conditions)->orderBy('time_point DESC')->limit(200),*/
            'query' => $query,
            'pagination' => false,
        ]);

        $model = new $this->modelClass;
        $models = $dataProvider->getModels();
        $dataProvider->setModels($models);

        return $dataProvider;
    }
}
