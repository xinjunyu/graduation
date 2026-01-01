<?php
require_once 'functions.php';

header('Content-Type: application/json');

// 检查登录状态
if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$limit = 50; // 限制返回数量

if (empty($type) || empty($keyword)) {
    echo json_encode([]);
    exit;
}

try {
    global $pdo;
    $results = [];

    switch ($type) {
        case 'user':
            // 搜索用户：用户名或真实姓名
            $stmt = $pdo->prepare("SELECT id, username, realname FROM users WHERE username LIKE ? OR realname LIKE ? LIMIT ?");
            $stmt->execute(["%$keyword%", "%$keyword%", $limit]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $results[] = [
                    'value' => $row['username'], // 搜索框填入的值
                    'label' => $row['realname'] . ' (' . $row['username'] . ')' // 显示的值
                ];
            }
            break;

        case 'item':
            // 搜索考评项目：名称
            $stmt = $pdo->prepare("SELECT id, name FROM assessment_items WHERE name LIKE ? LIMIT ?");
            $stmt->execute(["%$keyword%", $limit]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $results[] = [
                    'value' => $row['name'],
                    'label' => $row['name']
                ];
            }
            break;

        case 'assessment':
            // 搜索考评：标题
            $stmt = $pdo->prepare("SELECT id, title FROM assessments WHERE title LIKE ? LIMIT ?");
            $stmt->execute(["%$keyword%", $limit]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $results[] = [
                    'value' => $row['title'],
                    'label' => $row['title']
                ];
            }
            break;
    }

    // 如果结果不足5条，添加一个提示项（可选，根据用户需求“不足5条就显示不足的数量”理解为显示空占位或者只是数量少）
    // 用户原话：“不足5条就显示不足的数量”。这可能意味着 UI 上要体现空位，或者仅仅是返回实际数量。
    // 通常 Datalist 只是显示有的。这里我们返回实际结果，前端负责 UI 展示逻辑。

    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
