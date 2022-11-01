<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'log' => [
            'flushInterval' => 10,
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\DbTarget',
                    'exportInterval' => 10,
                    'levels' => ['info', 'warning'],
                    'categories' => ['yii\web\Session:*'],
                    'prefix' => function ($message) {
                        $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
                        $userName = $user ? $user->identity->username : 'Guest';
                        return "[$userName]";
                    },
                    'logVars' => ['_SESSION', '_GET', '_COOKIE'],
                    'maskVars' => ['_SESSION.__authKey', '_SESSION.__captcha/site/captcha']
                ],
            ],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'authManager' => [
            'class' => 'yii\rbac\DbManager',
        ],
        'odata' => [
            'class' => 'common\Helpers\http_request'
        ]
    ],

];
