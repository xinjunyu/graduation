<?php
// 引入配置文件（如果存在）
$config = [];
$dbname = 'assessment_system';

if (file_exists('includes/config.php')) {
    $config = require_once 'includes/config.php';
    $dbname = $config['db']['dbname'];
    $host = $config['db']['host'];
    $username = $config['db']['username'];
    $password = $config['db']['password'];
} else {
    // 默认配置
    $host = 'localhost';
    $username = 'root';
    $password = '';
}

try {
    // 创建PDO实例，连接到MySQL服务器
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 创建数据库
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "数据库创建成功\n";
    
    // 连接到创建的数据库
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 用户表
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        realname VARCHAR(50) NOT NULL,
        role ENUM('admin', 'teacher', 'student') NOT NULL,
        department VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "用户表创建成功\n";
    
    // 考评项目表
    $pdo->exec("CREATE TABLE IF NOT EXISTS assessment_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        weight DECIMAL(5,2) NOT NULL,
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    echo "考评项目表创建成功\n";
    
    // 考评表
    $pdo->exec("CREATE TABLE IF NOT EXISTS assessments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        status ENUM('draft', 'active', 'completed') NOT NULL DEFAULT 'draft',
        created_by INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    echo "考评表创建成功\n";
    
    // 考评参与表
    $pdo->exec("CREATE TABLE IF NOT EXISTS assessment_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        UNIQUE KEY unique_participant (assessment_id, user_id)
    )");
    echo "考评参与表创建成功\n";
    
    // 评分表
    $pdo->exec("CREATE TABLE IF NOT EXISTS scores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assessment_id INT NOT NULL,
        item_id INT NOT NULL,
        evaluator_id INT NOT NULL,
        evaluatee_id INT NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        comment TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES assessment_items(id),
        FOREIGN KEY (evaluator_id) REFERENCES users(id),
        FOREIGN KEY (evaluatee_id) REFERENCES users(id),
        UNIQUE KEY unique_score (assessment_id, item_id, evaluator_id, evaluatee_id)
    )");
    echo "评分表创建成功\n";
    
    // 插入默认管理员用户
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (username, password, realname, role, department) VALUES 
        ('admin', '$admin_password', '管理员', 'admin', '管理部')");
    echo "默认管理员用户插入成功\n";
    
    // 插入默认考评项目
    $pdo->exec("INSERT IGNORE INTO assessment_items (name, description, weight, created_by) VALUES 
        ('工作态度', '包括责任心、主动性、团队合作等', 25.00, 1),
        ('工作能力', '包括专业技能、学习能力、解决问题能力等', 30.00, 1),
        ('工作业绩', '包括工作完成质量、效率、成果等', 35.00, 1),
        ('综合素质', '包括沟通能力、创新能力、职业道德等', 10.00, 1)");
    echo "默认考评项目插入成功\n";
    
    // 插入默认考评
    $pdo->exec("INSERT IGNORE INTO assessments (title, start_time, end_time, status, created_by) VALUES 
        ('2025年度综合考评', '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', 1)");
    echo "默认考评插入成功\n";
    
    echo "\n数据库初始化完成！\n";
    echo "管理员账号：admin\n";
    echo "管理员密码：admin123\n";
    
} catch(PDOException $e) {
    die("数据库操作失败: " . $e->getMessage());
}
?>