/**
 * Content Scroll Manager - Pet & Repet Admin
 * Gerencia o comportamento de scroll da página de categorias
 */

document.addEventListener('DOMContentLoaded', function() {
    // Garantir que o body e html tenham o overflow correto
    document.body.style.overflowX = 'hidden';
    document.documentElement.style.overflowX = 'hidden';
    
    // Verificar se estamos na página de categorias
    if (document.getElementById('categoriesTable')) {
        setupCategoryPageScroll();
    }
});

function setupCategoryPageScroll() {
    // Garantir que o container principal tenha scroll
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        contentWrapper.style.overflowY = 'auto';
        contentWrapper.style.overflowX = 'hidden';
        contentWrapper.style.height = '100vh';
    }
    
    // Ajustar altura da tabela container baseado no viewport
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        const header = document.querySelector('.page-header');
        const toolbar = document.querySelector('.toolbar');
        
        if (header && toolbar) {
            const headerHeight = header.offsetHeight;
            const toolbarHeight = toolbar.offsetHeight;
            const padding = 100; // padding adicional para espaçamento
            
            const maxHeight = window.innerHeight - headerHeight - toolbarHeight - padding;
            tableContainer.style.maxHeight = `${maxHeight}px`;
        }
    }
    
    // Configurar scroll suave para elementos específicos
    const scrollableElements = document.querySelectorAll('.table-container, .content-wrapper');
    scrollableElements.forEach(element => {
        element.style.scrollBehavior = 'smooth';
    });
    
    // Listener para redimensionamento da janela
    window.addEventListener('resize', function() {
        adjustTableHeight();
    });
}

function adjustTableHeight() {
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        const header = document.querySelector('.page-header');
        const toolbar = document.querySelector('.toolbar');
        
        if (header && toolbar) {
            const headerHeight = header.offsetHeight;
            const toolbarHeight = toolbar.offsetHeight;
            const padding = 100;
            
            const maxHeight = window.innerHeight - headerHeight - toolbarHeight - padding;
            tableContainer.style.maxHeight = `${maxHeight}px`;
        }
    }
}

// Função para scroll suave para o topo
function scrollToTop() {
    const contentWrapper = document.querySelector('.content-wrapper');
    if (contentWrapper) {
        contentWrapper.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
}

// Expor função globalmente se necessário
window.scrollToTop = scrollToTop;
