// 强制解除授权限制的脚本
(function() {
    // 等待页面加载完成
    function fixAuth() {
        try {
            // 尝试获取全局状态对象
            if (window.WW && window.WW.state) {
                window.WW.state.auth = true;
                window.WW.state.authLoading = false;
                console.log('✅ 授权状态已强制设置为 true');
            }
            
            // 尝试获取其他可能的状态对象
            if (window.$store && window.$store.state) {
                window.$store.state.auth = true;
                window.$store.state.authLoading = false;
            }
            
            // 清除可能的授权检查
            localStorage.removeItem('auth_check');
            sessionStorage.removeItem('auth_check');
            
            // 强制刷新组件状态
            if (window.location.pathname.includes('manager')) {
                setTimeout(() => {
                    const authElements = document.querySelectorAll('[class*="auth"]');
                    authElements.forEach(el => {
                        if (el.style.display === 'none' || el.hidden) {
                            el.style.display = '';
                            el.hidden = false;
                        }
                    });
                }, 1000);
            }
            
        } catch (e) {
            console.log('授权修复脚本执行中:', e);
        }
    }
    
    // 立即执行一次
    fixAuth();
    
    // 定期检查并修复
    setInterval(fixAuth, 2000);
    
    // 页面加载完成后再执行一次
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixAuth);
    } else {
        setTimeout(fixAuth, 500);
    }
})();