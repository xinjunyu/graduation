<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 检查项目ID是否存在
if (!isset($_GET['id'])) {
    redirect('item_management.php');
}

$item_id = (int)$_GET['id'];

// 删除考评项目
global $pdo;
$stmt = $pdo->prepare("DELETE FROM assessment_items WHERE id = ?");
$stmt->execute([$item_id]);

// 重定向到项目列表页
redirect("item_management.php?success=考评项目删除成功");
?>