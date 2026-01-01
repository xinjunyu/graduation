<?php
// 引入配置文件
$config = require_once 'config.php';

// 数据库连接
$dbConfig = $config['db'];

try {
    // 创建PDO实例
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
} catch(PDOException $e) {
    // 记录错误日志（如果有日志系统）
    $errorMsg = "数据库连接失败: " . $e->getMessage();
    if ($config['app']['debug']) {
        die($errorMsg);
    } else {
        die("数据库连接失败，请联系管理员");
    }
}
?>