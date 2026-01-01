<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 获取当前用户信息
$user = get_logged_in_user();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $title = validate_form($_POST['title']);
    $start_time = validate_form($_POST['start_time']);
    $end_time = validate_form($_POST['end_time']);
    $status = validate_form($_POST['status']);
    
    // 检查开始时间是否早于结束时间
    if (strtotime($start_time) >= strtotime($end_time)) {
        redirect("add_assessment.php?error=开始时间必须早于结束时间");
    }
    
    // 插入考评数据
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO assessments (title, start_time, end_time, status, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$title, $start_time, $end_time, $status, $user['id']]);
    
    // 获取新创建的考评ID
    $assessment_id = $pdo->lastInsertId();
    
    // 重定向到考评列表页
    redirect("assessment_management.php?success=考评添加成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        新增考评
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">考评信息</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="add_assessment.php" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="title">考评标题 *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-col">
                            <label for="status">状态 *</label>
                            <select id="status" name="status" required>
                                <option value="draft">草稿</option>
                                <option value="active">进行中</option>
                                <option value="completed">已完成</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="start_time">开始时间 *</label>
                            <input type="datetime-local" id="start_time" name="start_time" required>
                        </div>
                        <div class="form-col">
                            <label for="end_time">结束时间 *</label>
                            <input type="datetime-local" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">保存</button>
                        <a href="assessment_management.php" class="btn btn-default">取消</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>