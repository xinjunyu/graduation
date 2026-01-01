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
    $name = validate_form($_POST['name']);
    $description = validate_form($_POST['description']);
    $weight = validate_form($_POST['weight']);
    
    // 检查项目名称是否已存在
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM assessment_items WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        redirect("add_item.php?error=考评项目名称已存在");
    }
    
    // 插入项目数据
    $stmt = $pdo->prepare("INSERT INTO assessment_items (name, description, weight, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $description, $weight, $user['id']]);
    
    // 重定向到项目列表页
    redirect("item_management.php?success=考评项目添加成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        新增考评项目
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">项目信息</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="add_item.php" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="name">项目名称 *</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-col">
                            <label for="weight">权重 *</label>
                            <input type="number" id="weight" name="weight" step="0.01" min="0" max="100" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="description">描述</label>
                            <textarea id="description" name="description" rows="5"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">保存</button>
                        <a href="item_management.php" class="btn btn-default">取消</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>