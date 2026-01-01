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

// 获取用户信息
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect('user_management.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $username = validate_form($_POST['username']);
    $realname = validate_form($_POST['realname']);
    $role = validate_form($_POST['role']);
    $department = validate_form($_POST['department']);
    $password = validate_form($_POST['password']);
    
    // 检查用户名是否已被其他用户使用
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        redirect("edit_user.php?id=$user_id&error=用户名已存在");
    }
    
    // 构建更新语句
    if ($password) {
        // 更新密码
        $hashed_password = encrypt_password($password);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, realname = ?, role = ?, department = ? WHERE id = ?");
        $stmt->execute([$username, $hashed_password, $realname, $role, $department, $user_id]);
    } else {
        // 不更新密码
        $stmt = $pdo->prepare("UPDATE users SET username = ?, realname = ?, role = ?, department = ? WHERE id = ?");
        $stmt->execute([$username, $realname, $role, $department, $user_id]);
    }
    
    // 重定向到用户列表页
    redirect("user_management.php?success=用户更新成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        编辑用户
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">用户信息</h3>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="username">用户名 *</label>
                            <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="password">密码（不填写则保持不变）</label>
                            <input type="password" id="password" name="password">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="realname">真实姓名 *</label>
                            <input type="text" id="realname" name="realname" value="<?php echo $user['realname']; ?>" required>
                        </div>
                        <div class="form-col">
                            <label for="role">角色 *</label>
                            <select id="role" name="role" required>
                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                                <option value="teacher" <?php echo $user['role'] === 'teacher' ? 'selected' : ''; ?>>教师</option>
                                <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>学生</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="department">部门/班级 *</label>
                            <input type="text" id="department" name="department" value="<?php echo $user['department']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">保存</button>
                        <a href="user_management.php" class="btn btn-default">取消</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>