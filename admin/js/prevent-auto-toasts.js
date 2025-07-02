// Este script substitui todos os pop-ups automáticos na página
console.log("Carregado script para remover pop-ups automáticos");

// Sobrescrever a função de load para evitar os toasts automáticos
const originalAddEventListener = window.addEventListener;
window.addEventListener = function(type, listener, options) {
    if (type === 'load') {
        const wrappedListener = function(event) {
            // Chamar o listener original
            const result = listener.call(this, event);
            
            // Agora prevenir quaisquer chamadas para showGlobalToast com "Sistema de gestão"
            const originalShowGlobalToast = window.showGlobalToast;
            if (originalShowGlobalToast) {
                window.showGlobalToast = function(message, type) {
                    if (message && message.includes("Sistema de gestão de categorias carregado")) {
                        console.log("Toast prevenido:", message);
                        return false;
                    }
                    if (message && message.includes("Sistema de notificações carregado")) {
                        console.log("Toast prevenido:", message);
                        return false;
                    }
                    return originalShowGlobalToast(message, type);
                };
            }
            return result;
        };
        
        // Usar o método original com o listener modificado
        return originalAddEventListener.call(this, type, wrappedListener, options);
    }
    // Caso contrário, usar o comportamento normal
    return originalAddEventListener.call(this, type, listener, options);
};

// Também sobrescrever ToastManager para evitar mensagens automáticas
window.addEventListener('DOMContentLoaded', function() {
    if (window.ToastManager) {
        const originalShowToast = window.ToastManager.showToast;
        window.ToastManager.showToast = function(message, type) {
            if (message && message.includes("Sistema de gestão de categorias carregado")) {
                console.log("Toast ToastManager prevenido:", message);
                return false;
            }
            if (message && message.includes("Sistema de notificações carregado")) {
                console.log("Toast ToastManager prevenido:", message);
                return false;
            }
            return originalShowToast.call(this, message, type);
        };
    }
});
