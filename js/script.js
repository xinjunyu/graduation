// 表单验证函数
function validateLoginForm() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (username.trim() === '') {
        alert('请输入用户名');
        return false;
    }
    
    if (password.trim() === '') {
        alert('请输入密码');
        return false;
    }
    
    return true;
}

// 通用表单验证函数
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    for (const input of inputs) {
        if (input.value.trim() === '') {
            alert('请填写所有必填字段');
            input.focus();
            return false;
        }
    }
    
    return true;
}

// 确认删除函数
function confirmDelete(message) {
    return confirm(message || '确定要删除吗？');
}

// 显示成功消息
function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 15px;
        border-radius: 5px;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// 显示错误消息
function showErrorMessage(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #f44336;
        color: white;
        padding: 15px;
        border-radius: 5px;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.remove();
    }, 3000);
}

// 表格排序函数
function sortTable(table, column, ascending = true) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // 排序行
    rows.sort((a, b) => {
        const aVal = a.cells[column].textContent.trim();
        const bVal = b.cells[column].textContent.trim();
        
        // 数字比较
        if (!isNaN(aVal) && !isNaN(bVal)) {
            return ascending ? parseFloat(aVal) - parseFloat(bVal) : parseFloat(bVal) - parseFloat(aVal);
        }
        
        // 字符串比较
        return ascending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
    });
    
    // 重新添加行
    rows.forEach(row => tbody.appendChild(row));
}

// 初始化表格排序
function initTableSorting() {
    const tables = document.querySelectorAll('.sortable-table');
    
    tables.forEach(table => {
        const headers = table.querySelectorAll('th');
        headers.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.dataset.sortColumn = index;
            header.dataset.sortAscending = 'true';
            
            header.addEventListener('click', () => {
                const ascending = header.dataset.sortAscending === 'true';
                sortTable(table, index, !ascending);
                header.dataset.sortAscending = (!ascending).toString();
            });
        });
    });
}

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', () => {
    initTableSorting();
    
    // 为所有表单添加验证
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!validateForm(form)) {
                e.preventDefault();
            }
        });
    });
});
