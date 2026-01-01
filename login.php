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
        // 统一重定向至首页并附带成功参数
        redirect('index.php?login_success=1');
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            // 注销成功提示
            if (urlParams.has('logout_success')) {
                showGlobalModal('👋 注销成功', '您已安全退出综合考评系统。', '返回登录');
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        function showGlobalModal(title, message, btnText, callback) {
            let overlay = document.getElementById('global-modal-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = 'global-modal-overlay';
                overlay.className = 'modal-overlay';
                overlay.innerHTML = `
                <div class="modal-box">
                    <div class="modal-icon" style="color: #1890ff;">ℹ</div>
                    <div class="modal-title"></div>
                    <div class="modal-message"></div>
                    <button class="modal-btn"></button>
                </div>
            `;
                document.body.appendChild(overlay);
            }

            overlay.querySelector('.modal-title').textContent = title;
            overlay.querySelector('.modal-message').textContent = message;
            overlay.querySelector('.modal-btn').textContent = btnText;

            overlay.style.display = 'flex';
            setTimeout(() => overlay.classList.add('active'), 10);

            overlay.querySelector('.modal-btn').onclick = function() {
                overlay.classList.remove('active');
                setTimeout(() => {
                    overlay.style.display = 'none';
                    if (callback) callback();
                }, 300);
            };
        }

        function validateLoginForm() {
            // 原有的表单验证逻辑（如果存在）
            return true;
        }
    </script>
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