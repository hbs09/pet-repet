/**
 * Sidebar functionality for Pet&Repet admin panel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Adjust sidebar based on screen size
    function adjustSidebar() {
        try {
            const windowWidth = window.innerWidth;
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (!sidebar) return;
            
            if (windowWidth <= 576) {
                // Mobile view
                sidebar.style.display = 'none';
                if (mainContent) {
                    mainContent.style.marginLeft = '0';
                    mainContent.style.width = '100%';
                }
            } else {
                // Desktop/tablet view
                sidebar.style.display = 'flex';
                if (mainContent) {
                    if (windowWidth <= 768) {
                        mainContent.style.marginLeft = '180px';
                        mainContent.style.width = 'calc(100% - 180px)';
                    } else if (windowWidth <= 992) {
                        mainContent.style.marginLeft = '220px';
                        mainContent.style.width = 'calc(100% - 220px)';
                    } else {
                        mainContent.style.marginLeft = '280px';
                        mainContent.style.width = 'calc(100% - 280px)';
                    }
                }
            }
        } catch (err) {
            console.error('Erro ao ajustar layout da sidebar:', err);
        }
    }

    // Toggle sidebar visibility for mobile
    window.toggleSidebar = function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar) return;
        
        if (sidebar.style.display === 'none' || sidebar.style.display === '') {
            sidebar.style.display = 'flex';
            if (mainContent) {
                mainContent.style.opacity = '0.3';
                mainContent.style.pointerEvents = 'none';
            }
        } else {
            sidebar.style.display = 'none';
            if (mainContent) {
                mainContent.style.opacity = '1';
                mainContent.style.pointerEvents = 'auto';
            }
        }
    }

    // Initialize mobile toggle button if it doesn't exist
    function initMobileToggle() {
        // Only create if screen is small and button doesn't exist
        if (window.innerWidth <= 576 && !document.querySelector('.sidebar-toggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'sidebar-toggle';
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            toggleBtn.setAttribute('aria-label', 'Toggle Sidebar');
            toggleBtn.onclick = toggleSidebar;
            document.body.appendChild(toggleBtn);
        }
    }

    // Run on page load and window resize
    window.addEventListener('load', function() {
        adjustSidebar();
        initMobileToggle();
    });
    
    window.addEventListener('resize', function() {
        adjustSidebar();
        initMobileToggle();
    });
});
