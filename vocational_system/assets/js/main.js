/**
 * 主 JavaScript 文件
 * 中职学生技能成长档案系统
 */

// DOM 加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    console.log('中职学生技能成长档案系统已加载');
    
    // 初始化所有功能模块
    initFormValidation();
    initModals();
    initNotifications();
});

// 表单验证初始化
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

// 表单验证函数
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            showInputError(input, '此字段为必填项');
        } else {
            clearInputError(input);
        }
        
        // 手机号验证
        if (input.type === 'tel' || input.id === 'phone') {
            const phonePattern = /^1[3-9]\d{9}$/;
            if (input.value && !phonePattern.test(input.value)) {
                isValid = false;
                showInputError(input, '请输入有效的 11 位手机号码');
            }
        }
        
        // 邮箱验证
        if (input.type === 'email') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (input.value && !emailPattern.test(input.value)) {
                isValid = false;
                showInputError(input, '请输入有效的邮箱地址');
            }
        }
        
        // 密码长度验证
        if (input.type === 'password' && input.minLength > 0) {
            if (input.value.length < input.minLength) {
                isValid = false;
                showInputError(input, `密码长度不能少于${input.minLength}位`);
            }
        }
    });
    
    return isValid;
}

// 显示输入错误
function showInputError(input, message) {
    input.classList.add('is-invalid');
    
    let errorElement = input.nextElementSibling;
    if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
        errorElement = document.createElement('div');
        errorElement.className = 'invalid-feedback';
        input.parentNode.insertBefore(errorElement, input.nextSibling);
    }
    
    errorElement.textContent = message;
}

// 清除输入错误
function clearInputError(input) {
    input.classList.remove('is-invalid', 'is-valid');
    
    const errorElement = input.nextElementSibling;
    if (errorElement && errorElement.classList.contains('invalid-feedback')) {
        errorElement.remove();
    }
}

// 模态框初始化
function initModals() {
    // 打开模态框
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', function() {
            const modalId = this.dataset.modalTarget;
            const modal = document.getElementById(modalId);
            if (modal) {
                openModal(modal);
            }
        });
    });
    
    // 关闭模态框
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // ESC 键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                closeModal(modal);
            });
        }
    });
}

// 打开模态框
function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// 关闭模态框
function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// 通知系统初始化
function initNotifications() {
    // 自动隐藏通知
    document.querySelectorAll('.alert[data-auto-hide]').forEach(alert => {
        const timeout = parseInt(alert.dataset.autoHide) || 5000;
        setTimeout(() => {
            hideNotification(alert);
        }, timeout);
    });
}

// 显示通知
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        ${message}
        <button class="modal-close" onclick="this.parentElement.remove()" style="margin-left: auto;">&times;</button>
    `;
    notification.style.position = 'fixed';
    notification.style.top = '80px';
    notification.style.right = '20px';
    notification.style.zIndex = '3000';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    if (duration > 0) {
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
    }
    
    return notification;
}

// 隐藏通知
function hideNotification(notification) {
    notification.style.opacity = '0';
    notification.style.transition = 'opacity 0.3s';
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// 获取通知图标
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || icons.info;
}

// AJAX 请求封装
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            showNotification('请求失败，请稍后重试', 'error');
            throw error;
        });
}

// 表格排序功能
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = table.dataset.sortOrder === 'asc';
    table.dataset.sortOrder = isAscending ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        }
        
        return isAscending 
            ? aText.localeCompare(bText, 'zh-CN') 
            : bText.localeCompare(aText, 'zh-CN');
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// 搜索过滤表格
function filterTable(tableId, searchInputId) {
    const table = document.getElementById(tableId);
    const input = document.getElementById(searchInputId);
    
    if (!table || !input) return;
    
    const filter = input.value.toUpperCase();
    const tr = table.getElementsByTagName('tr');
    
    for (let i = 1; i < tr.length; i++) {
        let showRow = false;
        const td = tr[i].getElementsByTagName('td');
        
        for (let j = 0; j < td.length; j++) {
            if (td[j]) {
                const txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    showRow = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = showRow ? '' : 'none';
    }
}

// 确认对话框
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// 格式化日期
function formatDate(dateString, format = 'YYYY-MM-DD') {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 节流函数
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// 本地存储封装
const storage = {
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (error) {
            console.error('Storage Error:', error);
            return false;
        }
    },
    
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Storage Error:', error);
            return defaultValue;
        }
    },
    
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (error) {
            console.error('Storage Error:', error);
            return false;
        }
    },
    
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (error) {
            console.error('Storage Error:', error);
            return false;
        }
    }
};

// 导出功能
window.vocationalSystem = {
    validateForm,
    showInputError,
    clearInputError,
    openModal,
    closeModal,
    showNotification,
    hideNotification,
    ajaxRequest,
    sortTable,
    filterTable,
    confirmAction,
    formatDate,
    debounce,
    throttle,
    storage
};

console.log(' vocationalSystem 工具库已加载');
