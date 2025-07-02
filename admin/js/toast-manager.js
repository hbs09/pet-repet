/**
 * Toast Manager - Gerenciador de notificações toast para Pet & Repet
 * Este arquivo centraliza a funcionalidade de notificações toast para evitar duplicações
 * 
 * @author Pet&Repet
 * @version 1.1 - Remoção de popups automáticos
 */

// Controle para evitar exibição duplicada de toasts
const ToastManager = {
    shownToasts: {},
    
    // Mostrar um toast padronizado, evitando duplicações de mensagens idênticas
    showToast: function(message, type = 'info') {
        console.log("ToastManager: Exibindo toast:", message, type);
        
        // Se for o toast verde de sistema carregado e já foi mostrado, não mostrar novamente
        const toastKey = `${message}-${type}`;
        if (this.shownToasts[toastKey]) {
            console.log("ToastManager: Toast já exibido anteriormente, ignorando duplicação:", toastKey);
            return;
        }
        
        // Marcar este toast como já exibido
        this.shownToasts[toastKey] = true;
        
        // Remover ícones HTML (<i>) e emojis do texto da mensagem para padronização
        let cleanMessage = message;
        try {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = message;
            cleanMessage = tempDiv.textContent || tempDiv.innerText || message;
            
            // Remover emojis comuns usados em notificações (✅, ❌, etc.)
            cleanMessage = cleanMessage.replace(/[\u{1F300}-\u{1F5FF}\u{1F900}-\u{1F9FF}\u{1F600}-\u{1F64F}\u{1F680}-\u{1F6FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}✅❌]/ug, '');
            
            // Remover espaços extras que possam ter ficado
            cleanMessage = cleanMessage.replace(/\s+/g, ' ').trim();
        } catch (err) {
            console.error("Erro ao limpar mensagem:", err);
            // Em caso de erro, manter a mensagem original
        }
        
        // Se o Toastify não estiver disponível, usar alert como fallback
        if (typeof Toastify !== 'function') {
            alert(cleanMessage);
            return;
        }
        
        // Configurar estilo baseado no tipo
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
        
        // Exibir o toast padronizado no centro
        try {
            Toastify({
                text: cleanMessage,
                duration: duration,
                gravity: "top", // sempre no topo
                position: "center", // sempre centralizado
                style: {
                    background: background,
                    borderRadius: "8px",
                    boxShadow: "0 3px 10px rgba(0,0,0,0.2)",
                    fontSize: "14px",
                    padding: "12px 20px",
                    zIndex: 9999
                }
            }).showToast();
            return true;
        } catch (e) {
            console.error("ToastManager: Erro ao exibir toast:", e);
            alert(cleanMessage);
            return false;
        }
    },
    
    // Função para mostrar um toast de atenção para exclusão de categorias (laranja)
    // Esta função só é chamada quando o usuário clica em excluir
    showDeleteWarning: function() {
        this.showToast("Atenção: Para excluir categorias, certifique-se de que não há produtos associados ou que existam categorias alternativas disponíveis.", "warning");
    }
};

// Função para confirmar exclusão de categoria com aviso apropriado
// FUNÇÃO DESATIVADA para evitar duplicação com confirmDeleteModal em categories-script.js
// function confirmCategoryDeletion(categoryId, isMainCategory) {
//     // Código comentado para evitar duplicação
// }

// Compatibilidade com o antigo showGlobalToast
window.showGlobalToast = function(message, type) {
    return ToastManager.showToast(message, type);
};

// Apenas verifica se o Toastify está disponível quando a página carrega
// NÃO mostra nenhuma notificação automática
window.addEventListener('load', function() {
    console.log('ToastManager: Status do Toastify na janela:', typeof window.Toastify);
    
    // Verificar se o Toastify está disponível após o carregamento completo
    if (typeof Toastify === 'undefined') {
        console.error('ToastManager: Toastify não está disponível após carregamento completo');
        // Tentar carregar o Toastify localmente, mas sem exibir toasts automáticos
        var script = document.createElement('script');
        script.src = '../scr/toastify.min.js';
        document.head.appendChild(script);
        
        script.onload = function() {
            console.log('ToastManager: Toastify carregado localmente');
            // Removido o toast automático de carregamento
        };
        
        script.onerror = function() {
            console.error('ToastManager: Falha ao carregar o Toastify');
            // Removido o alerta automático
        };
    }
});
