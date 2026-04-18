/**
 * 中职技能竞赛训练辅助系统 - 主 JavaScript 文件
 */

// DOM 加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有交互功能
    initModals();
    initTooltips();
    initFormValidation();
    initAutoSave();
});

// 模态框功能
function initModals() {
    document.querySelectorAll('[data-modal]').forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
            }
        });
    });
    
    // 关闭模态框
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });
    
    // ESC 键关闭模态框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                modal.classList.remove('active');
            });
        }
    });
}

// 工具提示功能
function initTooltips() {
    document.querySelectorAll('[data-tooltip]').forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: #fff;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 12px;
                z-index: 9999;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

// 表单验证
function initFormValidation() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                    
                    // 显示错误消息
                    let errorDiv = field.parentNode.querySelector('.form-error');
                    if (!errorDiv) {
                        errorDiv = document.createElement('div');
                        errorDiv.className = 'form-error';
                        errorDiv.textContent = '此项为必填项';
                        field.parentNode.appendChild(errorDiv);
                    }
                } else {
                    field.classList.remove('error');
                    const errorDiv = field.parentNode.querySelector('.form-error');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                }
                
                // 邮箱验证
                if (field.type === 'email' && field.value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(field.value)) {
                        isValid = false;
                        field.classList.add('error');
                        
                        let errorDiv = field.parentNode.querySelector('.form-error');
                        if (!errorDiv) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'form-error';
                            errorDiv.textContent = '请输入有效的邮箱地址';
                            field.parentNode.appendChild(errorDiv);
                        }
                    }
                }
                
                // 手机号验证
                if (field.type === 'tel' && field.value) {
                    const phoneRegex = /^1[3-9]\d{9}$/;
                    if (!phoneRegex.test(field.value)) {
                        isValid = false;
                        field.classList.add('error');
                        
                        let errorDiv = field.parentNode.querySelector('.form-error');
                        if (!errorDiv) {
                            errorDiv = document.createElement('div');
                            errorDiv.className = 'form-error';
                            errorDiv.textContent = '请输入有效的 11 位手机号';
                            field.parentNode.appendChild(errorDiv);
                        }
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // 输入时移除错误状态
        form.querySelectorAll('[required], [type="email"], [type="tel"]').forEach(field => {
            field.addEventListener('input', function() {
                this.classList.remove('error');
                const errorDiv = this.parentNode.querySelector('.form-error');
                if (errorDiv) {
                    errorDiv.remove();
                }
            });
        });
    });
}

// 自动保存功能
function initAutoSave() {
    const autoSaveForms = document.querySelectorAll('form[data-auto-save]');
    
    autoSaveForms.forEach(form => {
        const saveInterval = parseInt(form.getAttribute('data-auto-save')) || 30000;
        
        setInterval(() => {
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            localStorage.setItem('autosave_' + form.id, JSON.stringify(data));
            
            // 显示保存提示
            showNotification('草稿已自动保存', 'success');
        }, saveInterval);
        
        // 页面加载时恢复数据
        const savedData = localStorage.getItem('autosave_' + form.id);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(key => {
                    const field = form.querySelector('[name="' + key + '"]');
                    if (field) {
                        field.value = data[key];
                    }
                });
            } catch (e) {
                console.error('恢复自动保存数据失败:', e);
            }
        }
    });
}

// 显示通知
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
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

// 本地存储工具
const storage = {
    get: function(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            return defaultValue;
        }
    },
    
    set: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    },
    
    remove: function(key) {
        localStorage.removeItem(key);
    },
    
    clear: function() {
        localStorage.clear();
    }
};

// AJAX 请求工具
const ajax = {
    get: function(url, params = {}) {
        return this.request('GET', url, params);
    },
    
    post: function(url, data = {}) {
        return this.request('POST', url, data);
    },
    
    request: function(method, url, data = {}) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        if (method === 'GET') {
            const queryString = new URLSearchParams(data).toString();
            url += (url.includes('?') ? '&' : '?') + queryString;
        } else {
            options.body = JSON.stringify(data);
        }
        
        return fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            });
    }
};

// 格式化日期
function formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    
    return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
}

// 格式化数字（添加千分位）
function formatNumber(num) {
    return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// 获取 URL 参数
function getUrlParam(name, defaultValue = null) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name) || defaultValue;
}

// 滚动到顶部
function scrollToTop(duration = 300) {
    const start = window.pageYOffset;
    const change = -start;
    const startTime = performance.now();
    
    function animateScroll(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // 缓动函数
        const easeInOutQuad = t => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
        
        window.scrollTo(0, start + change * easeInOutQuad(progress));
        
        if (progress < 1) {
            requestAnimationFrame(animateScroll);
        }
    }
    
    requestAnimationFrame(animateScroll);
}

// 检查元素是否在视口中
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// 懒加载图片
function lazyLoadImages() {
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

// 复制到剪贴板
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('已复制到剪贴板', 'success');
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
            showNotification('已复制到剪贴板', 'success');
            return true;
        } catch (e) {
            showNotification('复制失败', 'error');
            return false;
        } finally {
            document.body.removeChild(textarea);
        }
    }
}

// 确认对话框
function confirmAction(message = '确定要执行此操作吗？') {
    return new Promise(resolve => {
        if (confirm(message)) {
            resolve(true);
        } else {
            resolve(false);
        }
    });
}

// 打印功能
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>打印</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                @media print {
                    body { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${element.innerHTML}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// 导出表格为 CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            let text = col.innerText.replace(/"/g, '""');
            rowData.push('"' + text + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// 初始化完成
console.log('中职技能竞赛训练辅助系统 - JavaScript 已加载');
