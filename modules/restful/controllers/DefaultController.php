<?php

namespace app\modules\restful\controllers;
use yii\log\Logger;
use yii\web\Controller;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    public static function saveModel($class, $value)
    {
        $model = new $class();
        $class = substr($class, strripos($class, "\\")+1);
        $data = [$class => $value];
        if (!$model->load($data))
        {
            \Yii::getLogger()->log("FAIL LOADING DATA: " , Logger::LEVEL_INFO);
            return 0;
        }
        try
        {
            if (!$model->save())
            {
                \Yii::getLogger()->log("FAIL SAVING DATA: " , Logger::LEVEL_INFO);
                //echo "FAIL SAVING DATA: \n" . implode("\n", $value);
                //var_dump($model->getErrors());
            }
            return 1;
        }
        catch (\Exception $e) 
        {
            //echo 'EXCEPTION: '. $e->getMessage(), "\n";
            \Yii::getLogger()->log("FAIL SAVING DATA: " . $e->getMessage(), Logger::LEVEL_INFO);
        }
    }
}
