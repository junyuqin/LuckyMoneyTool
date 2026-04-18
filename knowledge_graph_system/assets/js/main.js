/**
 * 中职专业课程知识图谱系统 - 主 JavaScript 文件
 */

/**
 * 显示 Toast 消息提示
 * @param {string} message - 消息内容
 * @param {string} type - 消息类型：success, error, warning
 * @param {number} duration - 显示时长（毫秒）
 */
function showToast(message, type = 'success', duration = 3000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    // 根据类型添加图标
    let icon = '';
    switch (type) {
        case 'success':
            icon = '✅';
            break;
        case 'error':
            icon = '❌';
            break;
        case 'warning':
            icon = '⚠️';
            break;
        default:
            icon = 'ℹ️';
    }
    
    toast.innerHTML = `<span>${icon}</span><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);
    
    // 自动移除
    setTimeout(() => {
        toast.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

/**
 * HTML 转义函数
 * @param {string} text - 需要转义的文本
 * @returns {string} 转义后的文本
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 移动端菜单切换
 */
function toggleMobileMenu() {
    const sidebar = document.getElementById('mobileSidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

/**
 * 确认对话框
 * @param {string} message - 确认信息
 * @param {function} callback - 确认后的回调函数
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * 表单验证 - 手机号
 * @param {string} phone - 手机号码
 * @returns {boolean} 是否有效
 */
function validatePhone(phone) {
    const pattern = /^1[3-9]\d{9}$/;
    return pattern.test(phone);
}

/**
 * 表单验证 - 密码强度
 * @param {string} password - 密码
 * @returns {object} 包含强度等级和提示信息
 */
function validatePassword(password) {
    const result = {
        valid: false,
        strength: 'weak',
        message: ''
    };
    
    // 长度检查
    if (password.length < 8) {
        result.message = '密码长度不能少于 8 位';
        return result;
    }
    
    // 检查是否包含空格
    if (password.includes(' ')) {
        result.message = '密码不能包含空格';
        return result;
    }
    
    let strengthScore = 0;
    
    // 包含小写字母
    if (/[a-z]/.test(password)) strengthScore++;
    // 包含大写字母
    if (/[A-Z]/.test(password)) strengthScore++;
    // 包含数字
    if (/\d/.test(password)) strengthScore++;
    // 包含特殊字符
    if (/[^a-zA-Z0-9]/.test(password)) strengthScore++;
    
    // 判断强度
    if (strengthScore >= 4) {
        result.strength = 'strong';
        result.valid = true;
        result.message = '密码强度：强';
    } else if (strengthScore >= 2) {
        result.strength = 'medium';
        result.valid = true;
        result.message = '密码强度：中';
    } else {
        result.strength = 'weak';
        result.message = '密码强度：弱，建议包含大小写字母、数字和特殊符号';
    }
    
    return result;
}

/**
 * 表单验证 - 邮箱
 * @param {string} email - 邮箱地址
 * @returns {boolean} 是否有效
 */
function validateEmail(email) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

/**
 * 格式化日期时间
 * @param {string|Date} date - 日期对象或字符串
 * @param {string} format - 格式：datetime, date, time
 * @returns {string} 格式化后的日期字符串
 */
function formatDateTime(date, format = 'datetime') {
    if (!date) return '';
    
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    
    switch (format) {
        case 'date':
            return `${year}-${month}-${day}`;
        case 'time':
            return `${hours}:${minutes}:${seconds}`;
        default:
            return `${year}-${month}-${day} ${hours}:${minutes}`;
    }
}

/**
 * 获取 URL 参数
 * @param {string} name - 参数名
 * @returns {string|null} 参数值
 */
function getUrlParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

/**
 * AJAX 请求封装
 * @param {string} url - 请求地址
 * @param {object} options - 请求选项
 * @returns {Promise} Promise 对象
 */
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        },
        body: null
    };
    
    const config = { ...defaultOptions, ...options };
    
    return fetch(url, config)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

/**
 * 显示加载动画
 * @param {string} targetId - 目标元素 ID
 */
function showLoading(targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;
    
    target.innerHTML = `
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>加载中...</p>
        </div>
    `;
}

/**
 * 隐藏加载动画
 * @param {string} targetId - 目标元素 ID
 */
function hideLoading(targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;
    
    const loading = target.querySelector('.loading-spinner');
    if (loading) {
        loading.remove();
    }
}

/**
 * 表格排序
 * @param {string} tableId - 表格 ID
 * @param {number} columnIndex - 列索引
 * @param {boolean} ascending - 是否升序
 */
function sortTable(tableId, columnIndex, ascending = true) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        // 尝试转换为数字比较
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return ascending ? aNum - bNum : bNum - aNum;
        }
        
        // 字符串比较
        return ascending 
            ? aText.localeCompare(bText, 'zh-CN')
            : bText.localeCompare(aText, 'zh-CN');
    });
    
    // 重新插入排序后的行
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * 本地存储封装
 */
const storage = {
    /**
     * 设置存储
     * @param {string} key - 键
     * @param {*} value - 值
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (e) {
            console.error('Storage set error:', e);
        }
    },
    
    /**
     * 获取存储
     * @param {string} key - 键
     * @returns {*} 值
     */
    get(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (e) {
            console.error('Storage get error:', e);
            return null;
        }
    },
    
    /**
     * 移除存储
     * @param {string} key - 键
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {
            console.error('Storage remove error:', e);
        }
    },
    
    /**
     * 清空存储
     */
    clear() {
        try {
            localStorage.clear();
        } catch (e) {
            console.error('Storage clear error:', e);
        }
    }
};

/**
 * 防抖函数
 * @param {function} func - 需要防抖的函数
 * @param {number} wait - 等待时间（毫秒）
 * @returns {function} 防抖后的函数
 */
function debounce(func, wait = 300) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

/**
 * 节流函数
 * @param {function} func - 需要节流的函数
 * @param {number} limit - 限制时间（毫秒）
 * @returns {function} 节流后的函数
 */
function throttle(func, limit = 300) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * 复制文本到剪贴板
 * @param {string} text - 需要复制的文本
 * @returns {Promise<boolean>} 是否成功
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showToast('复制成功', 'success');
        return true;
    } catch (err) {
        // 降级方案
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        
        try {
            document.execCommand('copy');
            showToast('复制成功', 'success');
            return true;
        } catch (e) {
            showToast('复制失败', 'error');
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

/**
 * 滚动到顶部
 */
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

/**
 * 图片懒加载
 */
function initLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

/**
 * 页面加载完成后执行
 */
document.addEventListener('DOMContentLoaded', function() {
    // 初始化懒加载
    initLazyLoad();
    
    // 检查登录状态提示
    const logoutSuccess = getUrlParam('logout');
    if (logoutSuccess === 'success') {
        showToast('已成功退出登录', 'success');
        // 清除 URL 参数
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // 自动隐藏 alert 消息
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // 表单输入实时验证
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        input.addEventListener('input', debounce(function(e) {
            const result = validatePassword(e.target.value);
            const hintElement = e.target.parentElement.querySelector('.password-hint');
            
            if (hintElement) {
                hintElement.textContent = result.message;
                hintElement.className = `password-hint hint-${result.strength}`;
            }
        }, 300));
    });
    
    console.log('中职专业课程知识图谱系统已加载完成');
});

// 页面可见性变化处理
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        console.log('页面已隐藏');
    } else {
        console.log('页面已显示');
    }
});

// 窗口大小变化处理
window.addEventListener('resize', throttle(function() {
    console.log('窗口大小已变化');
}, 200));
