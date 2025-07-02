/**
 * Script temporário para substituir o script inline em categories.php
 * Este script remove os pop-ups automáticos quando a página é carregada
 */

// Função global para exibir toasts
function showGlobalToast(message, type = 'info') {
    console.log("Exibindo toast global:", message, type);
    
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
        // Usar configuração padronizada de toast
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

// Função para confirmar exclusão de categoria com aviso apropriado
// Esta função foi substituída por confirmDeleteModal em categories-script.js
// Mantida apenas por compatibilidade
function confirmCategoryDeletion(categoryId, isMainCategory) {
    // Exibir aviso de atenção específico para o contexto
    showGlobalToast("Atenção: Para excluir categorias, certifique-se de que não há produtos associados ou que existam categorias alternativas disponíveis.", "warning");
    
    // Aguardar um momento para que o usuário leia o aviso antes de mostrar a confirmação
    setTimeout(function() {
        let confirmMessage = isMainCategory ? 
            'Tem certeza que deseja excluir esta categoria principal? Os produtos associados serão movidos para outra categoria principal disponível.' : 
            'Tem certeza que deseja excluir esta subcategoria? Produtos diretamente associados serão movidos para outra subcategoria ou para a categoria pai, e associações secundárias serão removidas.';
        
        if (confirm(confirmMessage)) {
            // Usar o método POST para exclusão em vez de GET
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
        }
    }, 1500); // Aguardar 1.5 segundos para que o usuário veja o aviso
}

// Inicializar eventos assim que o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM carregado, sistema inicializado');
    // Avisos removidos - agora só são mostrados em resposta a ações do usuário
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
            // Toast de sistema de notificações removido conforme solicitado
        };
        
        script.onerror = function() {
            console.error('Falha ao carregar o Toastify');
            console.error('Não foi possível carregar o sistema de notificações.');
        };
    }
});
