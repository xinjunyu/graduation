<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>综合考评系统</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>综合考评系统</h1>
            </div>
            <nav>
                <ul>
                    <?php if (isset($_SESSION['role'])): ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="../admin/user_management.php">用户管理</a></li>
                            <li><a href="../admin/item_management.php">考评项目管理</a></li>
                            <li><a href="../admin/assessment_management.php">考评管理</a></li>
                        <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                            <li><a href="../teacher/scoring.php">评分</a></li>
                            <li><a href="../teacher/result_query.php">结果查询</a></li>
                        <?php elseif ($_SESSION['role'] === 'student'): ?>
                            <li><a href="../student/result_query.php">结果查询</a></li>
                        <?php endif; ?>
                        <li><a href="../logout.php">退出登录</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
