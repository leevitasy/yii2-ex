<?php
return [
    'class' => 'yii\db\Connection',

    // common configuration for masters
    'masterConfig' => [
        'username' => 'master',
        'password' => 'PUSHvJAdDdXFs7nx',
        'attributes' => [
            // use a smaller connection timeout
            PDO::ATTR_TIMEOUT => 3,
        ],
        'charset' => 'utf8',
    ],

    // list of master configurations
    'masters' => [
        ['dsn' => 'mysql:host=localhost;dbname=yii2_ex'],
    ],

    // common configuration for slaves
    'slaveConfig' => [
        'username' => 'slave',
        'password' => 'UfzL4XQTVyQa86xu',
        'attributes' => [
            // use a smaller connection timeout
            PDO::ATTR_TIMEOUT => 3,
        ],
        'charset' => 'utf8',
    ],

    // list of slave configurations
    'slaves' => [
        ['dsn' => 'mysql:host=localhost;dbname=yii2_ex'],
    ],
];
