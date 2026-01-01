<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>综合考评系统</title>
    <!-- 引入多种中文字体和英文字体 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Ma+Shan+Zheng&family=Montserrat:wght@600&family=Noto+Sans+SC:wght@300;400;500;700&family=Noto+Serif+SC:wght@600;900&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <?php
    // 自动检测相对于根目录的路径前缀
    $path_prefix = (file_exists('css/style.css')) ? '' : '../';
    ?>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>css/style.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);

            // 登录成功提示
            if (urlParams.has('login_success')) {
                showGlobalModal('🎉 登录成功', '欢迎回来！系统已准备就绪，祝您使用愉快。', '开始使用');
                // 清理 URL，防止刷新后再次弹出
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
                    <div class="modal-icon">✔</div>
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

            // 强制回流以触发动画
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
    </script>
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
                        <li><a href="<?php echo $path_prefix; ?>index.php">首页</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="<?php echo $path_prefix; ?>admin/user_management.php">用户管理</a></li>
                            <li><a href="<?php echo $path_prefix; ?>admin/item_management.php">考评项目管理</a></li>
                            <li><a href="<?php echo $path_prefix; ?>admin/assessment_management.php">考评管理</a></li>
                        <?php elseif ($_SESSION['role'] === 'teacher'): ?>
                            <li><a href="<?php echo $path_prefix; ?>teacher/scoring.php">评分</a></li>
                            <li><a href="<?php echo $path_prefix; ?>teacher/result_query.php">结果查询</a></li>
                        <?php elseif ($_SESSION['role'] === 'student'): ?>
                            <li><a href="<?php echo $path_prefix; ?>student/result_query.php">结果查询</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo $path_prefix; ?>logout.php">退出登录</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>