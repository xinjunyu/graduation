<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 检查用户ID是否存在
if (!isset($_GET['id'])) {
    redirect('user_management.php');
}

$user_id = (int)$_GET['id'];

// 不允许删除自己
if ($user_id === $_SESSION['user_id']) {
    redirect('user_management.php?error=不允许删除当前登录用户');
}

// 删除用户
global $pdo;
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$user_id]);

// 重定向到用户列表页
redirect("user_management.php?success=用户删除成功");
?>