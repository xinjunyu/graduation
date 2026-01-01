document.addEventListener('DOMContentLoaded', function () {
    // 使用事件委托监听排序链接和分页链接的点击
    document.body.addEventListener('click', function (e) {
        // 查找最近的匹配链接
        const link = e.target.closest('.sort-link, .pagination-btn');

        // 如果不是目标链接
        if (!link) {
            return;
        }

        // 如果链接被禁用，阻止默认行为并返回
        if (link.hasAttribute('disabled') || link.classList.contains('disabled')) {
            e.preventDefault();
            return;
        }

        // 如果带有 target="_blank"，则忽略
        if (link.getAttribute('target') === '_blank') {
            return;
        }

        // 如果是删除按钮或其他操作，忽略
        if (link.classList.contains('btn-danger') || link.classList.contains('btn-primary')) {
            return;
        }

        e.preventDefault();

        const url = link.getAttribute('href');
        if (!url || url === '#') return;

        // 添加加载状态（可选：给表格添加半透明效果）
        const tableContainer = link.closest('.card-body');
        if (tableContainer) {
            tableContainer.style.opacity = '0.5';
            tableContainer.style.transition = 'opacity 0.2s';
        }

        fetch(url)
            .then(response => response.text())
            .then(html => {
                // 解析返回的 HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 找到新页面中的对应容器 (只更新包含表格的 card-body)
                // 策略：找到包含 .sortable-table 的 card-body
                const newTableCardBody = Array.from(doc.querySelectorAll('.card-body')).find(el => el.querySelector('.sortable-table'));

                if (newTableCardBody) {
                    // 找到当前页面中对应的容器
                    const currentTableCardBody = Array.from(document.querySelectorAll('.card-body')).find(el => el.querySelector('.sortable-table'));

                    if (currentTableCardBody) {
                        // 更新内容
                        currentTableCardBody.innerHTML = newTableCardBody.innerHTML;

                        // 恢复透明度
                        currentTableCardBody.style.opacity = '1';

                        // 更新 URL，不刷新页面
                        window.history.pushState({}, '', url);
                    } else {
                        // 如果找不到对应容器，回退到普通跳转
                        window.location.href = url;
                    }
                } else {
                    // 如果新页面结构不对，回退到普通跳转
                    window.location.href = url;
                }
            })
            .catch(err => {
                console.error('AJAX Load Failed:', err);
                // 失败时回退到普通跳转
                window.location.href = url;
            });
    });

    // 监听浏览器的后退/前进按钮，实现页面内容正确切换
    window.addEventListener('popstate', function () {
        // 重载页面以保证状态正确（或者也可以像上面一样实现 AJAX 后退，但重载更简单可靠）
        window.location.reload();
    });
});
