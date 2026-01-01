<?php
// 数据库配置
return [
    'db' => [
        'host' => 'localhost',
        'dbname' => 'assessment_system',
        'username' => 'root',
        'password' => '1984833525zxc',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    ],
    'app' => [
        'name' => '综合考评系统',
        'debug' => true
    ]
];
?>