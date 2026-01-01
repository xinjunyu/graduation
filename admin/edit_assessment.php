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

// 获取考评信息
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM assessments WHERE id = ?");
$stmt->execute([$assessment_id]);
$assessment = $stmt->fetch();

if (!$assessment) {
    redirect('assessment_management.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $title = validate_form($_POST['title']);
    $start_time = validate_form($_POST['start_time']);
    $end_time = validate_form($_POST['end_time']);
    $status = validate_form($_POST['status']);
    
    // 检查开始时间是否早于结束时间
    if (strtotime($start_time) >= strtotime($end_time)) {
        redirect("edit_assessment.php?id=$assessment_id&error=开始时间必须早于结束时间");
    }
    
    // 更新考评数据
    $stmt = $pdo->prepare("UPDATE assessments SET title = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?");
    $stmt->execute([$title, $start_time, $end_time, $status, $assessment_id]);
    
    // 重定向到考评列表页
    redirect("assessment_management.php?success=考评更新成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        编辑考评
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
            
            <form action="edit_assessment.php?id=<?php echo $assessment_id; ?>" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="title">考评标题 *</label>
                            <input type="text" id="title" name="title" value="<?php echo $assessment['title']; ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="status">状态 *</label>
                            <select id="status" name="status" required>
                                <option value="draft" <?php echo $assessment['status'] === 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="active" <?php echo $assessment['status'] === 'active' ? 'selected' : ''; ?>>进行中</option>
                                <option value="completed" <?php echo $assessment['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="start_time">开始时间 *</label>
                            <input type="datetime-local" id="start_time" name="start_time" value="<?php echo date('Y-m-d\TH:i', strtotime($assessment['start_time'])); ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="end_time">结束时间 *</label>
                            <input type="datetime-local" id="end_time" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($assessment['end_time'])); ?>" required>
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