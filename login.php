<?php
// 引入公共函数
require_once 'includes/functions.php';

// 检查是否已登录
if (is_logged_in()) {
    redirect('index.php');
}

// 处理登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = validate_form($_POST['username']);
    $password = $_POST['password'];  // 不对密码进行validate_form处理
    
    if (login($username, $password)) {
        // 根据用户角色重定向
        switch ($_SESSION['role']) {
            case 'admin':
                redirect('admin/user_management.php');
                break;
            case 'teacher':
                redirect('teacher/scoring.php');
                break;
            case 'student':
                redirect('student/result_query.php');
                break;
            default:
                redirect('index.php');
        }
    } else {
        redirect('login.php?error=用户名或密码错误');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 综合考评系统</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2>综合考评系统</h2>
            <h3>用户登录</h3>
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST" onsubmit="return validateLoginForm();">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="login-btn">登录</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>