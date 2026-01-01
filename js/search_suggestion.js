document.addEventListener('DOMContentLoaded', function () {
    // 获取当前页面类型
    const pagePath = window.location.pathname;
    let searchType = '';

    if (pagePath.includes('user_management.php')) {
        searchType = 'user';
    } else if (pagePath.includes('item_management.php')) {
        searchType = 'item';
    } else if (pagePath.includes('assessment_management.php')) {
        searchType = 'assessment';
    }

    // 如果不是这三个页面，不执行逻辑
    if (!searchType) return;

    const searchInput = document.getElementById('search');
    if (!searchInput) return;

    // 创建下拉建议容器
    const suggestionsBox = document.createElement('div');
    suggestionsBox.className = 'search-suggestions';
    searchInput.parentNode.appendChild(suggestionsBox);

    // 动态定位函数
    function positionSuggestions() {
        suggestionsBox.style.width = searchInput.offsetWidth + 'px';
        suggestionsBox.style.left = searchInput.offsetLeft + 'px';
        suggestionsBox.style.top = (searchInput.offsetTop + searchInput.offsetHeight) + 'px';
    }

    // 初始化位置
    positionSuggestions();

    // 监听窗口大小改变，保持同步
    window.addEventListener('resize', positionSuggestions);

    // 防抖函数
    let debounceTimer;

    searchInput.addEventListener('input', function () {
        const keyword = this.value.trim();
        clearTimeout(debounceTimer);

        if (keyword.length === 0) {
            suggestionsBox.style.display = 'none';
            suggestionsBox.classList.remove('active');
            return;
        }

        debounceTimer = setTimeout(() => {
            fetchSuggestions(searchType, keyword);
        }, 300);
    });

    // 隐藏下拉框当点击外部时
    document.addEventListener('click', function (e) {
        if (e.target !== searchInput && e.target !== suggestionsBox) {
            suggestionsBox.classList.remove('active');
            setTimeout(() => suggestionsBox.style.display = 'none', 200);
        }
    });

    // 监听输入框聚焦
    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length > 0 && suggestionsBox.innerHTML !== '') {
            suggestionsBox.style.display = 'block';
            setTimeout(() => suggestionsBox.classList.add('active'), 10);
        }
    });

    function fetchSuggestions(type, keyword) {
        // 使用相对路径获取接口
        const pathPrefix = (document.querySelector('link[href*="style.css"]').getAttribute('href').includes('../')) ? '../' : '';

        fetch(pathPrefix + 'includes/ajax_search.php?type=' + type + '&keyword=' + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                renderSuggestions(data);
            })
            .catch(error => console.error('Error:', error));
    }

    function renderSuggestions(data) {
        suggestionsBox.innerHTML = '';

        if (data.length === 0) {
            const emptyItem = document.createElement('div');
            emptyItem.className = 'suggestion-empty';
            emptyItem.textContent = '暂无匹配结果';
            suggestionsBox.appendChild(emptyItem);
        } else {
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.textContent = item.label; // 显示完整信息
                div.addEventListener('click', function () {
                    searchInput.value = item.value; // 填入搜索词
                    suggestionsBox.classList.remove('active');
                    suggestionsBox.style.display = 'none';
                    // 自动提交表单
                    searchInput.form.submit();
                });
                suggestionsBox.appendChild(div);
            });


        }

        suggestionsBox.style.display = 'block';
        setTimeout(() => suggestionsBox.classList.add('active'), 10);
    }
});
