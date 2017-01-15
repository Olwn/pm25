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
	if(count($data['data'])==0){
		$result['message'] = 'no data';
		return $result;
	}
        foreach ($data['data'] as $columns) {
            $ret = self::saveModel($this->modelClass, $columns);
	    $result['succeed_count'] += $ret['count'];
	    $result['message'] = $result['message'] . json_encode($ret['info']);
        }
        return $result;
    }

    public static function saveModel($class, $data)
    {
        $model = new $class();        
        foreach ($model->attributes as $key => $value) {
            if(isset($data[$key]))
                $model->__set($key, $data[$key]);
        }
        $ret = array('count' => 0, 'info'=> ''); 
        try
        {
            if (!$model->save())
            {
                //\Yii::getLogger()->log("FAIL SAVING DATA: " , Logger::LEVEL_INFO);
                //echo "FAIL SAVING DATA: \n" . $value;
				$ret['info'] = $model->getFirstErrors() . json_encode($data);
				return $ret;
            }
			$ret['count'] = 1;
            return $ret;
        }
        catch (\Exception $e) 
        {
            //echo 'EXCEPTION: '. $e->getMessage(), "\n";
            //\Yii::getLogger()->log("FAIL SAVING DATA: " . $e->getMessage(), Logger::LEVEL_INFO);
			$ret['count'] = 0;
			$ret['info'] = $e->getMessage() . 'Exception!!';
			return $ret;
        }
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
