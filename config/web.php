<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'bdtTsrHPyK89Lh9ywBvbo_oPygLtsVUH',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/air-quality', 
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/area--position', 
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/user-takein', 
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/mobile-data',
                    'extraPatterns' => ['search' => 'search']
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/device-data',
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/urban-air',
                ],
                    'create/data_pm25in' => 'restful/create/data_pm25in',
                    'create/data_device' => 'restful/create/data_device',
                    'create/data_mobile' => 'restful/create/data_mobile',
                    'create/data_urban'  => 'restful/create/urban_air',
       
            ],
        ],
    ],
    'modules' => [
        'restful' => [
            'class' => 'app\modules\restful\Module',
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;
