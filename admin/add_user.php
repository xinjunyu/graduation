<?php
// 引入公共函数
require_once '../includes/functions.php';

// 检查登录状态和权限
check_permission('admin');

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证表单数据
    $username = validate_form($_POST['username']);
    $password = validate_form($_POST['password']);
    $realname = validate_form($_POST['realname']);
    $role = validate_form($_POST['role']);
    $department = validate_form($_POST['department']);
    
    // 检查用户名是否已存在
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        redirect("add_user.php?error=用户名已存在");
    }
    
    // 插入用户数据
    $hashed_password = encrypt_password($password);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, realname, role, department) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashed_password, $realname, $role, $department]);
    
    // 重定向到用户列表页
    redirect("user_management.php?success=用户添加成功");
}
?>
<?php include '../includes/header.php'; ?>

    <div class="page-title">
        新增用户
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
            
            <form action="add_user.php" method="POST" onsubmit="return validateForm(this);">
                <div class="form-container">
                    <div class="form-row">
                        <div class="form-col">
                            <label for="username">用户名 *</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-col">
                            <label for="password">密码 *</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="realname">真实姓名 *</label>
                            <input type="text" id="realname" name="realname" required>
                        </div>
                        <div class="form-col">
                            <label for="role">角色 *</label>
                            <select id="role" name="role" required>
                                <option value="">请选择角色</option>
                                <option value="admin">管理员</option>
                                <option value="teacher">教师</option>
                                <option value="student">学生</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label for="department">部门/班级 *</label>
                            <input type="text" id="department" name="department" required>
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