// Função global para exibir toasts - redireciona para o ToastManager centralizado
function showGlobalToast(message, type = 'info') {
    // Verificar se o ToastManager está disponível
    if (typeof ToastManager !== 'undefined' && typeof ToastManager.showToast === 'function') {
        // Usar o ToastManager centralizado
        return ToastManager.showToast(message, type);
    } else {
        console.warn("ToastManager não disponível, usando implementação local temporária.");
        
        // Implementação de fallback caso o ToastManager não esteja carregado
        var background = "#3498db"; // azul para info
        var duration = 3000;
        
        if (type === 'success') {
            background = "#2ecc71"; // verde
            duration = 4000;
        } else if (type === 'error') {
            background = "#e74c3c"; // vermelho
            duration = 6000;
        } else if (type === 'warning') {
            background = "linear-gradient(to right, #ff7e5f, #feb47b)"; // laranja
            duration = 5000;
        }
        
        try {
            Toastify({
                text: message,
                duration: duration,
                gravity: "top",
                position: "center", // Centralizado como solicitado
                style: {
                    background: background,
                    borderRadius: "8px",
                    boxShadow: "0 3px 10px rgba(0,0,0,0.2)",
                    fontSize: "14px",
                    padding: "12px 20px"
                }
            }).showToast();
            return true;
        } catch (e) {
            console.error("Erro ao exibir toast:", e);
            alert(message);
            return false;
        }
    }
}

// Função para confirmar exclusão de categoria com aviso apropriado
// FUNÇÃO DESATIVADA para evitar duplicação com confirmDeleteModal em categories-script.js
// function confirmCategoryDeletion(categoryId, isMainCategory) {
//     // Código comentado para evitar duplicação
// }

// Inicializar eventos assim que o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, sistema inicializado');
    // Não exibir mais mensagens automáticas ao carregar a página
});

// Verificação final quando a página estiver totalmente carregada
window.addEventListener('load', function() {
    console.log('Status do Toastify na janela:', typeof window.Toastify);
    
    // Verifica se o Toastify está disponível após o carregamento completo
    if (typeof Toastify !== 'undefined') {
        console.log('Toastify carregado com sucesso');
        // Toast de bem-vindo removido conforme solicitado
    } else {
        console.error('Toastify não está disponível após carregamento completo');
        // Tentar carregar o Toastify localmente
        var script = document.createElement('script');
        script.src = '../scr/toastify.min.js';
        document.head.appendChild(script);
        
        script.onload = function() {
            console.log('Toastify carregado localmente');
            // Toast de notificações removido conforme solicitado
        };
        
        script.onerror = function() {
            console.error('Falha ao carregar o Toastify');
            console.error('Não foi possível carregar o sistema de notificações.');
        };
    }
});
