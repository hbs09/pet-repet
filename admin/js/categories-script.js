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

// Função para confirmar exclusão de categoria com o modal personalizado
// Função renomeada para evitar conflitos com outras implementações
function confirmDeleteModal(categoryId, isMainCategory) {
    // Configurar o modal de confirmação
    const deleteModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const categoryToDeleteId = document.getElementById('categoryToDeleteId');
    const deleteConfirmTitle = document.getElementById('deleteConfirmTitle');
    const deleteConfirmMessage = document.getElementById('deleteConfirmMessage');
    const deleteWarningMessage = document.getElementById('deleteWarningMessage');
    
    if (!deleteModal) return;
    
    // Configurar título e mensagem baseado no tipo de categoria
    deleteConfirmTitle.innerHTML = isMainCategory 
        ? '<i class="fas fa-folder me-1"></i> Excluir Categoria Principal'
        : '<i class="fas fa-folder-open me-1"></i> Excluir Subcategoria';
        
    deleteConfirmMessage.innerHTML = isMainCategory 
        ? 'Os produtos associados a esta categoria principal serão movidos para outra categoria disponível. Esta operação não pode ser desfeita.'
        : 'Produtos associados a esta subcategoria serão realocados para outra subcategoria ou para a categoria pai. Esta operação não pode ser desfeita.';
        
    deleteWarningMessage.innerHTML = isMainCategory
        ? '<strong>Aviso:</strong> A exclusão de categorias principais pode afetar a navegação na loja.'
        : '<strong>Aviso:</strong> A exclusão de subcategorias pode afetar a organização dos produtos.';
    
    // Armazenar o ID da categoria a ser excluída
    categoryToDeleteId.value = categoryId;
    
    // Configurar o botão de confirmação
    confirmDeleteBtn.onclick = function() {
        // Mostrar indicador de carregamento com pata
        const pawLoader = showPawLoader('Processando exclusão...');
        
        // Desativar botão durante o processamento
        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Excluindo...';
        
        // Criar e enviar um formulário para fazer a exclusão via POST em vez de GET
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'categories.php';
        form.style.display = 'none';
        
        // Adicionar campo para identificar a ação como delete
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);
        
        // Adicionar o ID da categoria a ser excluída
        const categoryIdInput = document.createElement('input');
        categoryIdInput.type = 'hidden';
        categoryIdInput.name = 'category_id';
        categoryIdInput.value = categoryId;
        form.appendChild(categoryIdInput);
        
        // Adicionar o formulário ao documento e enviá-lo
        document.body.appendChild(form);
        form.submit();
        
        // Fechar o modal
        bootstrap.Modal.getInstance(deleteModal).hide();
    };
    
    // Exibir o modal
    const modal = new bootstrap.Modal(deleteModal);
    modal.show();
}

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
