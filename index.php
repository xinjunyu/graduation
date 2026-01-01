<?php
// 引入公共函数
require_once 'includes/functions.php';

// 检查登录状态
if (!is_logged_in()) {
    redirect('login.php');
}

// 获取当前用户信息
$user = get_logged_in_user();

// 统计逻辑 (仅管理员需要显示)
$teacher_count = 0;
$student_count = 0;

if ($user['role'] === 'admin') {
    global $pdo;

    // 统计教师人数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $teacher_count = $stmt->fetchColumn();

    // 统计学生人数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $stmt->execute();
    $student_count = $stmt->fetchColumn();
}
?>
<?php include 'includes/header.php'; ?>

<div class="page-title">
    欢迎回来，<?php echo $user['realname']; ?>！
</div>

<?php if ($user['role'] === 'admin'): ?>
    <!-- 管理员首页 -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number">1</div>
            <div class="stat-label">待处理考评</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4</div>
            <div class="stat-label">考评项目</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $teacher_count; ?></div>
            <div class="stat-label">教师用户</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $student_count; ?></div>
            <div class="stat-label">学生用户</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">快捷操作</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="admin/user_management.php" class="btn btn-primary">用户管理</a>
                <a href="admin/item_management.php" class="btn btn-primary">考评项目管理</a>
                <a href="admin/assessment_management.php" class="btn btn-primary">考评管理</a>
            </div>
        </div>
    </div>
<?php elseif ($user['role'] === 'teacher'): ?>
    <!-- 教师首页 -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number">1</div>
            <div class="stat-label">当前考评</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4</div>
            <div class="stat-label">考评项目</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">0</div>
            <div class="stat-label">已评分人数</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">快捷操作</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="teacher/scoring.php" class="btn btn-primary">开始评分</a>
                <a href="teacher/result_query.php" class="btn btn-primary">查看结果</a>
            </div>
        </div>
    </div>
<?php elseif ($user['role'] === 'student'): ?>
    <!-- 学生首页 -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-number">1</div>
            <div class="stat-label">当前考评</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">4</div>
            <div class="stat-label">考评项目</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">快捷操作</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions">
                <a href="student/result_query.php" class="btn btn-primary">查看我的成绩</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">最新通知</h3>
    </div>
    <div class="card-body">
        <ul class="notification-list">
            <li>
                <span class="notification-date">2025-01-01</span>
                <span class="notification-content">2025年度综合考评已经开始，请各位老师及时完成评分任务。</span>
            </li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>