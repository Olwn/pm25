<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'homeurl' => 'index',
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
            'enableSession' => false,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',  
            'useFileTransport' =>false,//这句一定有，false发送邮件，true只是生成邮件在runtime文件夹下，不发邮件
            'transport' => [  
                'class' => 'Swift_SmtpTransport',  
                'host' => 'smtp.qq.com',  //每种邮箱的host配置不一样
                'username' => '386765657@qq.com',  
                'password' => '27271992olccnm',  
                'port' => '465',  
                'encryption' => 'ssl',
                ],   
            'messageConfig' => [  
                'charset'=>'UTF-8',  
                'from'=>['386765657@qq.com'=>'iLabTongji']  
                ],  
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
                    'controller' => 'restful/area-position', 
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/user-takein', 
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/mobile-data',
                    'extraPatterns' => [
                        'POST upload' => 'upload'
                    ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/device-data',
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/urban-air',
                    'extraPatterns' => [
                        'search' => 'search',
                        'search-history' => 'search-history',
                        'GET latest-days' => 'latest-days'
                        ]
                ],
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => 'restful/user',
                    'extraPatterns' => [
                        'POST gettoken' => 'gettoken',
                        'POST logon' => 'logon',
                        'POST login' => 'login',
                        'GET resetpassword' => 'find-password',
                        'GET reset' => 'reset',
                        'POST updatepassword' => 'update-password',
                        'test1' => 'test'
                    ],
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
