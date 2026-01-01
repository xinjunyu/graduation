<?php
// 启动会话
session_start();

// 引入数据库连接
require_once 'db.php';

// 检查用户是否已登录
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// 获取当前登录用户信息
function get_logged_in_user()
{
    if (is_logged_in()) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// 检查用户权限
function check_permission($role)
{
    $user = get_logged_in_user();
    if (!$user || $user['role'] !== $role) {
        $login_url = (file_exists('login.php')) ? 'login.php' : '../login.php';
        header("Location: $login_url");
        exit;
    }
}

// 密码加密
function encrypt_password($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// 验证用户登录
function login($username, $password)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

// 用户登出
function logout()
{
    session_unset();
    session_destroy();
    $login_url = (file_exists('login.php')) ? 'login.php' : '../login.php';
    header("Location: $login_url?logout_success=1");
    exit;
}

// 重定向函数
function redirect($url)
{
    header("Location: $url");
    exit;
}

// 生成随机字符串
function generate_random_string($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// 验证表单数据
function validate_form($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// 获取考评项目总分权重
function get_total_weight()
{
    global $pdo;
    $stmt = $pdo->query("SELECT SUM(weight) AS total_weight FROM assessment_items");
    $result = $stmt->fetch();
    return $result['total_weight'];
}

// 计算加权平均分
function calculate_weighted_average($scores, $items)
{
    $total = 0;
    $total_weight = 0;

    foreach ($items as $item) {
        $score = isset($scores[$item['id']]) ? $scores[$item['id']] : 0;
        $total += $score * $item['weight'];
        $total_weight += $item['weight'];
    }

    return $total_weight > 0 ? round($total / $total_weight, 2) : 0;
}

// 生成CSV文件
function generate_csv($data, $filename)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // 输出表头
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));

        // 输出数据
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }

    fclose($output);
    exit;
}
