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

// 获取项目信息
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM assessment_items WHERE id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    redirect('item_management.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $name = validate_form($_POST['name']);
    $description = validate_form($_POST['description']);
    $weight = validate_form($_POST['weight']);
    
    // 检查项目名称是否已被其他项目使用
    $stmt = $pdo->prepare("SELECT id FROM assessment_items WHERE name = ? AND id != ?");
    $stmt->execute([$name, $item_id]);
    if ($stmt->fetch()) {
        redirect("edit_item.php?id=$item_id&error=考评项目名称已存在");
    }
    
    // 更新项目数据
    $stmt = $pdo->prepare("UPDATE assessment_items SET name = ?, description = ?, weight = ? WHERE id = ?");
    $stmt->execute([$name, $description, $weight, $item_id]);
    
    // 重定向到项目列表页
    redirect("item_management.php?success=考评项目更新成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        编辑考评项目
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
            
            <form action="edit_item.php?id=<?php echo $item_id; ?>" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="name">项目名称 *</label>
                            <input type="text" id="name" name="name" value="<?php echo $item['name']; ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="weight">权重 *</label>
                            <input type="number" id="weight" name="weight" step="0.01" min="0" max="100" value="<?php echo $item['weight']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="description">描述</label>
                            <textarea id="description" name="description" rows="5"><?php echo $item['description']; ?></textarea>
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