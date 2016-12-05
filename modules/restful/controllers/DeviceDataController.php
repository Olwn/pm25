<?php

namespace app\modules\restful\controllers;

use Yii;
use yii\data\ActiveDataProvider;

class DeviceDataController extends CheckTokenController
{
    public $modelClass = 'app\models\DeviceData';
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
        $data = $this->queryWithConditions($conditions)->models;;
		foreach($data as &$val)
		{
				$val = array(
					'PM25' => $val['pm25'],
					'time_point' => $val['time_point']
				);
		}
		if(count($data) == 0)
		{
				return array(
						'status' => 0,
						'message' => 'no data',
						'data' => ''
				);
		}
		$ret = array(
				'status' =>  1,
				'message' => 'success',
				'data' => $data
		);
		return $ret;

    }

    public function queryWithConditions($conditions)
    {
        $query = (new \yii\db\Query())
            ->select('*')
            ->from('data_device');
            


        if(strlen($conditions['time_point']) < 15)
        {
            $query->where(['=', "DATE_FORMAT(time_point, '%Y-%m-%d')", $conditions['time_point']]);
        	unset($conditions['time_point']);
			$query->andWhere($conditions);
		}
		else
		{
			$query->where($conditions);
		}
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);

        $model = new $this->modelClass;
        $models = $dataProvider->getModels();
        $dataProvider->setModels($models);

        return $dataProvider;
    }
}
