    </main>
    <footer>
        <div class="footer-content">
            <p>&copy; 2026 综合考评系统. 版权所有：心语科技</p>
            <div class="footer-contact">
                <span>联系我们：</span>
                <a href="mailto:youx6067@gmail.com">youx6067@gmail.com</a>
            </div>
        </div>
    </footer>
    <?php
    // 确保 path_prefix 存在
    if (!isset($path_prefix)) {
        $path_prefix = (file_exists('css/style.css')) ? '' : '../';
    }
    ?>
    <script src="<?php echo $path_prefix; ?>js/search_suggestion.js"></script>
    <script src="<?php echo $path_prefix; ?>js/ajax_table.js"></script>
    </body>

    </html>