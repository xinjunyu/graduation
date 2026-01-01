<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 检查考评ID是否存在
if (!isset($_GET['id'])) {
    redirect('assessment_management.php');
}

$assessment_id = (int)$_GET['id'];

// 删除考评（关联的参与人员和评分记录会通过外键约束自动删除）
global $pdo;
$stmt = $pdo->prepare("DELETE FROM assessments WHERE id = ?");
$stmt->execute([$assessment_id]);

// 重定向到考评列表页
redirect("assessment_management.php?success=考评删除成功");
?>