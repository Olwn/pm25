<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use yii\console\Controller;
use yii\log\Logger;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Ming Yang <yougmark@icloud.com>
 * @since 2.0
 */
class AirQualityController extends Controller
{
    private $urlStations = "http://www.pm25.in/api/querys/all_cities.json";
    private $urlCities = "http://www.pm25.in/api/querys/aqi_ranking.json";
    private $datePath;
    private $token;
    private $dateLastQuery;
    

    public function init()
    {
        parent::init();
        $this->token = require(__DIR__.'/token.php');
        $this->datePath = __DIR__ . "/dateLastQuery.txt";
        $this->dateLastQuery = $this->getDateLastQuery();
    }
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
        //echo $message . "\n";
        $this->actionCreate();
    }

    /**
     * This command create new records of city air qualities.
     */
    public function actionCreate()
    {
        $nextQueryDate = $this->dateLastQuery->add(new \DateInterval('PT1H'));
        //print_r($nextQueryDate);
        while (1)
        {
            $now = new \DateTime();
            //print_r($now);
            $interval = $nextQueryDate->diff($now);
            if ($interval->invert == 1) // $now is ealier
            {
                echo $interval->format('%H:%I:%S');
                sleep(1);
                for ($i=0;$i<8;$i++)
                {
                    echo chr(8);
                }
                continue;
            }

            $nextQueryDate = $this->dateLastQuery->add(new \DateInterval('PT1H'));
            $allStationsJsonData = $this->queryAllStationsJSONData();
            $mixed = json_decode($allStationsJsonData, true);
            if (isset($mixed['error']))
            {
                echo $mixed['error'];
            }
            else 
            {
                echo "Dealing with " . count($mixed) . " records.";
				if (count($mixed) != 0)
				{
					$class = 'app\models\AirQuality';
					foreach ($mixed as $value)
					{
						$this->saveModel($class, $value);
					}
				}
            }
            
            $allCitiesJsonData = $this->queryOnlyCitiesJSONData();
            $mixed = json_decode($allCitiesJsonData, true);
            if (isset($mixed['error']))
            {
                echo $mixed['error'];
            }
            else
            {
                echo "Dealing with " . count($mixed) . " records.";
				if (count($mixed) != 0)
				{
					$class = 'app\models\CityAir';
					foreach ($mixed as $value)
					{
						$this->saveModel($class, $value);
					}
				}
            }
            $this->dateLastQuery = new \DateTime();
            $this->saveDateLastQuery();
        }
    }

    private function saveModel($class, $value)
    {
        $model = new $class();
        $class = substr($class, strripos($class, "\\")+1);
        $data = [$class => $value];
        if (!$model->load($data))
        {
            \Yii::getLogger()->log("FAIL LOADING DATA: " . implode("\n", $value), Logger::LEVEL_INFO);
        }
        try
        {
            if (!$model->save())
            {
                \Yii::getLogger()->log("FAIL SAVING DATA: " . implode("\n", $value), Logger::LEVEL_INFO);
                echo "FAIL SAVING DATA: \n" . implode("\n", $value);
                var_dump($model->getErrors());
            }
        }
        catch (\Exception $e) 
        {
            echo 'EXCEPTION: '. $e->getMessage(), "\n";
            \Yii::getLogger()->log("FAIL SAVING DATA: " . $e->getMessage(), Logger::LEVEL_INFO);
        }
    }

    /**
     * Query json data from pm25.in
     */
    private function queryAllStationsJSONData()
    {
        echo "Start querying air quality data from http://pm25.in\n";
        //$path = __DIR__ . "/../all_cities.json";
        $path = $this->urlStations . "?token=" . $this->token;
        try 
        {
            $content = file_get_contents($path);
        }
        catch (\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage(), "\n";
            return null;
        }
        //print_r($content);
        return $content;
    }


    private function queryOnlyCitiesJSONData()
    {
        echo "Start querying air quality data from http://pm25.in\n";
        //$path = __DIR__ . "/../all_cities.json";
        $path = $this->urlCities . "?token=" . $this->token;
        try 
        {
            $content = file_get_contents($path);
        }
        catch (\Exception $e)
        {
            echo 'EXCEPTION: ' . $e->getMessage(), "\n";
            return null;
        }
        //print_r($content);
        return $content;
    }
    /**
     * Restore last query date
     */
    private function getDateLastQuery()
    {
        $file = fopen($this->datePath, "a+");
        if (!filesize($this->datePath))
        {
            $now = new \DateTime();
            $now->sub(new \DateInterval('PT1H'));
            return $now;
        }
        $content = fread($file, filesize($this->datePath));
        fclose($file);
        return new \DateTime($content);
    }

    /**
     * Save last query date
     */
    private function saveDateLastQuery()
    {
        $file = fopen($this->datePath, "w"); 
        fwrite($file, $this->dateLastQuery->format('Y-m-d H:i:s'));
        fclose($file);
    }
}
