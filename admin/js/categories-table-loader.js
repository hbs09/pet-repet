/**
 * Categories Table Loader
 * Carrega dinamicamente as categorias na tabela com o novo design
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configurar listeners para o modal
    setupCategoryModalListeners();
    
    // Carregar a tabela de categorias
    loadCategoriesTable();
});

function loadCategoriesTable() {
    const tableBody = document.querySelector('#categoryTable tbody');
    if (!tableBody) return;
    
    // Fazer requisição AJAX para buscar categorias da base de dados
    fetch('ajax/get_categories_data.php')
        .then(response => response.json())
        .then(data => {
            // Limpar loading state
            tableBody.innerHTML = '';
            
            if (data.success && data.categories) {
                const categories = data.categories;
                
                // Gerar HTML das categorias
                let tableHTML = '';
                
                categories.forEach(category => {
                    // Categoria principal
                    tableHTML += generateCategoryRow(category, false);
                    
                    // Subcategorias
                    if (category.subcategories && category.subcategories.length > 0) {
                        category.subcategories.forEach(subcategory => {
                            tableHTML += generateCategoryRow(subcategory, true);
                        });
                    }
                });
                
                tableBody.innerHTML = tableHTML;
                
                // Adicionar event listeners para os botões
                addActionListeners();
                
                // Atualizar contadores
                updateCounters(categories);
            } else {
                // Mostrar mensagem de erro
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p class="mb-0">Erro ao carregar categorias: ${data.error || 'Erro desconhecido'}</p>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar categorias:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p class="mb-0">Erro ao carregar categorias. Verifique sua conexão.</p>
                    </td>
                </tr>
            `;
        });
}

function generateCategoryRow(category, isSubcategory = false) {
    const statusClass = category.is_active ? 'status-active' : 'status-inactive';
    const statusText = category.is_active ? 'Ativa' : 'Inativa';
    const statusIcon = category.is_active ? 'check-circle' : 'times-circle';
    
    const rowClass = isSubcategory ? 'subcategory-row' : '';
    const nameClass = isSubcategory ? 'subcategory-name' : '';
    
    return `
        <tr class="${rowClass}" data-category-id="${category.id}">
            <td>
                <div class="${nameClass}">
                    <div class="d-flex align-items-center">
                            <div class="category-info">
                            <h6 class="mb-1 fw-semibold">${category.name}</h6>
                            ${isSubcategory ? '<small class="text-muted">Subcategoria</small>' : '<small class="text-muted">Categoria Principal</small>'}
                        </div>
                    </div>
                </div>
            </td>
            <td class="text-center">
                <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                    <i class="fas fa-box me-1"></i>
                    ${category.product_count}
                </span>
            </td>
            <td class="text-center">
                <div class="status-badge ${statusClass}">
                    <div class="status-dot"></div>
                    ${statusText}
                </div>
            </td>
            <td class="text-center">
                <div class="action-buttons">
                    <button class="btn-action btn-edit" title="Editar categoria" data-action="edit" data-id="${category.id}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action btn-toggle" title="Alternar status" data-action="toggle" data-id="${category.id}">
                        <i class="fas fa-${category.is_active ? 'eye-slash' : 'eye'}"></i>
                    </button>
                    <button class="btn-action btn-delete" title="Excluir categoria" data-action="delete" data-id="${category.id}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function addActionListeners() {
    console.log('Adicionando event listeners aos botões de ação');
    
    // Edit buttons
    document.querySelectorAll('[data-action="edit"]').forEach(btn => {
        console.log('Adicionando listener para botão de editar:', btn);
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = this.getAttribute('data-id');
            console.log('Botão de editar clicado, ID da categoria:', categoryId);
            editCategory(categoryId);
        });
    });
    
    // Toggle buttons
    document.querySelectorAll('[data-action="toggle"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            toggleCategory(categoryId);
        });
    });
    
    // Delete buttons
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-id');
            deleteCategory(categoryId);
        });
    });
    
    console.log('Event listeners adicionados para', document.querySelectorAll('[data-action]').length, 'botões');
}

function editCategory(categoryId) {
    console.log('Editando categoria:', categoryId);
    
    // Mostrar indicador de carregamento
    showGlobalToast('Carregando dados da categoria...', 'info', 1500);
    
    // Fazer uma requisição AJAX para obter os dados da categoria
    const formData = new FormData();
    formData.append('category_id', categoryId);
    
    fetch('ajax/get_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Resposta recebida do servidor:', response);
        return response.json();
    })
    .then(data => {
        console.log('Dados da categoria recebidos:', data);
        if (data.success) {
            // Preencher o formulário de edição com os dados da categoria
            console.log('Preenchendo formulário de edição com dados:', data.category);
            fillEditCategoryForm(data.category);
            
            // Aguardar um pouco e depois abrir o modal de edição
            setTimeout(() => {
                // Atualizar o título do modal para edição
                const modalTitle = document.getElementById('editModalActionText');
                if (modalTitle) {
                    modalTitle.textContent = data.category.parent_id === null 
                        ? 'Editar Categoria Principal' 
                        : 'Editar Subcategoria';
                }
                
                // Abrir o modal de edição
                const modal = document.getElementById('editCategoryModal');
                if (modal) {
                    console.log('Abrindo modal de edição');
                    const bootstrapModal = new bootstrap.Modal(modal);
                    bootstrapModal.show();
                } else {
                    console.error('Modal editCategoryModal não encontrado');
                }
            }, 200); // Aguarda 200ms após preencher o formulário
        } else {
            console.error('Erro nos dados:', data);
            showGlobalToast('Erro: ' + data.error, 'error', 3000);
        }
    })
    .catch(error => {
        console.error('Erro ao carregar dados da categoria:', error);
        showGlobalToast('Erro ao carregar dados da categoria', 'error', 3000);
    });
}

/**
 * Preenche o formulário do modal de edição com os dados da categoria
 * @param {Object} category - Objeto contendo os dados da categoria
 */
function fillEditCategoryForm(category) {
    console.log('Preenchendo formulário de edição com categoria:', category);
    
    // Verificar se os elementos existem antes de preencher
    const categoryIdField = document.getElementById('edit_category_id');
    const categoryNameField = document.getElementById('edit_category_name');
    const categoryDescField = document.getElementById('edit_category_description');
    const parentSelect = document.getElementById('edit_parent_category');
    
    console.log('Elementos do modal de edição encontrados:');
    console.log('- edit_category_id:', categoryIdField);
    console.log('- edit_category_name:', categoryNameField);
    console.log('- edit_category_description:', categoryDescField);
    console.log('- edit_parent_category:', parentSelect);
    
    if (!categoryIdField || !categoryNameField || !categoryDescField) {
        console.error('Campos do formulário de edição não encontrados!');
        return;
    }
    
    // Preencher campos do formulário
    categoryIdField.value = category.id;
    categoryNameField.value = category.name || '';
    categoryDescField.value = category.description || '';
    
    console.log('Campos de edição preenchidos:');
    console.log('- ID:', categoryIdField.value);
    console.log('- Nome:', categoryNameField.value);
    console.log('- Descrição:', categoryDescField.value);
    
    // Selecionar categoria pai, se existir
    if (parentSelect) {
        parentSelect.value = category.parent_id || '';
        console.log('- Categoria pai:', parentSelect.value);
    }
    
    // Atualizar mensagem específica do modo de edição
    const editModeMessage = document.getElementById('editModeMessage');
    if (editModeMessage) {
        if (category.parent_id === null) {
            editModeMessage.innerHTML = `Editando a <strong>categoria principal</strong> "${category.name}".`;
        } else {
            editModeMessage.innerHTML = `Editando a <strong>subcategoria</strong> "${category.name}".`;
        }
    }
    
    console.log('Formulário de edição preenchido com sucesso!');
}

/**
 * Preenche o formulário do modal com os dados da categoria
 * @param {Object} category - Objeto contendo os dados da categoria
 */
function fillCategoryForm(category) {
    console.log('Preenchendo formulário com categoria:', category);
    
    // Verificar se os elementos existem antes de preencher
    const categoryIdField = document.getElementById('category_id');
    const categoryNameField = document.getElementById('category_name');
    const categoryDescField = document.getElementById('category_description');
    const parentSelect = document.getElementById('parent_category');
    
    console.log('Elementos encontrados:');
    console.log('- category_id:', categoryIdField);
    console.log('- category_name:', categoryNameField);
    console.log('- category_description:', categoryDescField);
    console.log('- parent_category:', parentSelect);
    
    if (!categoryIdField || !categoryNameField || !categoryDescField) {
        console.error('Campos do formulário não encontrados!');
        return;
    }
    
    // Preencher campos do formulário com métodos mais robustos
    console.log('Preenchendo campo ID com:', category.id);
    categoryIdField.value = category.id;
    categoryIdField.setAttribute('value', category.id);
    
    console.log('Preenchendo campo nome com:', category.name);
    categoryNameField.value = category.name || '';
    categoryNameField.setAttribute('value', category.name || '');
    
    console.log('Preenchendo campo descrição com:', category.description);
    categoryDescField.value = category.description || '';
    categoryDescField.textContent = category.description || '';
    categoryDescField.innerHTML = category.description || '';
    
    // Verificar se os valores foram definidos
    console.log('Valores após preenchimento:');
    console.log('- ID:', categoryIdField.value);
    console.log('- Nome:', categoryNameField.value);
    console.log('- Descrição:', categoryDescField.value);
    
    // Selecionar categoria pai, se existir
    if (parentSelect) {
        console.log('Preenchendo categoria pai com:', category.parent_id);
        parentSelect.value = category.parent_id || '';
        console.log('- Categoria pai:', parentSelect.value);
    }
    
    // Adicionar campo de ação para edição
    let actionInput = document.querySelector('#categoryForm input[name="action"]');
    if (!actionInput) {
        actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.id = 'form_action';
        const form = document.getElementById('categoryForm');
        if (form) form.appendChild(actionInput);
    }
    actionInput.value = 'edit';
    console.log('Campo action definido como:', actionInput.value);
    
    // Atualizar o botão de salvar
    const saveButton = document.getElementById('btnSaveCategory');
    if (saveButton) {
        saveButton.innerHTML = '<i class="fas fa-save me-2"></i><span>Atualizar Categoria</span>';
        console.log('Botão de salvar atualizado');
    }
    
    // Forçar atualização visual dos campos com vários métodos
    if (categoryNameField) {
        categoryNameField.dispatchEvent(new Event('input', { bubbles: true }));
        categoryNameField.dispatchEvent(new Event('change', { bubbles: true }));
        categoryNameField.focus();
        categoryNameField.blur();
    }
    
    if (categoryDescField) {
        categoryDescField.dispatchEvent(new Event('input', { bubbles: true }));
        categoryDescField.dispatchEvent(new Event('change', { bubbles: true }));
        categoryDescField.focus();
        categoryDescField.blur();
    }
    
    // Log final para verificar se tudo foi preenchido
    setTimeout(() => {
        console.log('Verificação final dos valores:');
        console.log('- ID final:', document.getElementById('category_id')?.value);
        console.log('- Nome final:', document.getElementById('category_name')?.value);
        console.log('- Descrição final:', document.getElementById('category_description')?.value);
        console.log('- Categoria pai final:', document.getElementById('parent_category')?.value);
    }, 50);
    
    console.log('Formulário preenchido com sucesso!');
}

/**
 * Atualiza o modal para o modo de edição
 * @param {Object} category - Objeto contendo os dados da categoria
 */
function updateModalForEdit(category) {
    // Alterar ícone do cabeçalho
    const headerIcon = document.querySelector('.modal-title i');
    if (headerIcon) {
        headerIcon.className = 'fas fa-edit me-2';
    }
    
    // Adicionar indicador visual de edição
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.classList.add('edit-mode');
        
        // Adicionar badge de edição ao título
        const modalActionText = document.getElementById('modalActionText');
        if (modalActionText) {
            const existingBadge = modalActionText.querySelector('.edit-category-badge');
            if (!existingBadge) {
                const badge = document.createElement('span');
                badge.className = 'edit-category-badge';
                badge.innerHTML = '<i class="fas fa-fingerprint me-1" style="font-size: 0.7rem;"></i>ID: ' + category.id;
                modalActionText.appendChild(badge);
            }
        }
        
        // Atualizar texto do botão de salvar
        const btnSaveCategoryText = document.getElementById('btnSaveCategoryText');
        if (btnSaveCategoryText) {
            btnSaveCategoryText.textContent = 'Atualizar Categoria';
        }
        
        // Mostrar informações específicas do modo de edição
        const editModeInfo = document.getElementById('editModeInfo');
        if (editModeInfo) {
            editModeInfo.style.display = 'block';
            
            // Atualizar mensagem específica
            const editModeMessage = document.getElementById('editModeMessage');
            if (editModeMessage) {
                if (category.parent_id === null) {
                    // É categoria principal
                    if (category.has_subcategories) {
                        editModeMessage.innerHTML = `Esta é uma <strong>categoria principal</strong> e possui subcategorias. 
                                                    Se mudar para subcategoria, é necessário remover ou realocar suas subcategorias primeiro.`;
                    } else {
                        editModeMessage.innerHTML = `Esta é uma <strong>categoria principal</strong>. Você pode editar seus dados ou 
                                                    transformá-la em subcategoria selecionando uma categoria pai.`;
                    }
                } else {
                    // É subcategoria
                    const parentName = getCategoryParentName(category.parent_id);
                    editModeMessage.innerHTML = `Esta é uma <strong>subcategoria</strong> de <strong>${parentName}</strong>. 
                                               Você pode mudar para outra categoria pai ou transformá-la em categoria principal.`;
                }
            }
        }
        
        // Esconder o alerta padrão de status
        const statusInfo = document.querySelector('.alert.alert-light');
        if (statusInfo) {
            statusInfo.style.display = 'none';
        }
    }
}

/**
 * Obtém o nome da categoria pai com base no ID
 * @param {number} parentId - ID da categoria pai
 * @return {string} - Nome da categoria pai ou texto padrão
 */
function getCategoryParentName(parentId) {
    const parentSelect = document.getElementById('parent_category');
    if (parentSelect) {
        const option = parentSelect.querySelector(`option[value="${parentId}"]`);
        if (option) {
            return option.textContent;
        }
    }
    return 'categoria principal';
}

/**
 * Resetar o modal de categoria para o estado original (modo de adição)
 */
function resetCategoryModal() {
    console.log('Resetando modal de categoria');
    
    // Resetar formulário
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.reset();
    }
    
    // Limpar campo de ID
    const categoryIdInput = document.getElementById('category_id');
    if (categoryIdInput) {
        categoryIdInput.value = '';
    }
    
    // Restaurar título e ícone
    const modalTitle = document.getElementById('modalActionText');
    if (modalTitle) {
        modalTitle.textContent = 'Adicionar Nova Categoria';
        
        // Remover badge de ID se existir
        const badge = modalTitle.querySelector('.edit-category-badge');
        if (badge) {
            badge.remove();
        }
    }
    
    // Restaurar ícone
    const headerIcon = document.querySelector('.modal-title i');
    if (headerIcon) {
        headerIcon.className = 'fas fa-plus-circle me-2';
        headerIcon.style.color = '#1e88e5';
    }
    
    // Restaurar texto do botão de salvar
    const saveButton = document.getElementById('btnSaveCategory');
    if (saveButton) {
        saveButton.disabled = false;
        saveButton.className = 'btn btn-primary';
        saveButton.style = '';
        
        // Restaurar ícone e texto do botão
        const btnSaveCategoryText = document.getElementById('btnSaveCategoryText');
        if (btnSaveCategoryText) {
            btnSaveCategoryText.textContent = 'Salvar Categoria';
        } else {
            saveButton.innerHTML = '<i class="fas fa-save me-2"></i> Salvar Categoria';
        }
    }
    
    // Ocultar o painel de informações de edição
    const editModeInfo = document.getElementById('editModeInfo');
    if (editModeInfo) {
        editModeInfo.style.display = 'none';
    }
    
    // Restaurar texto de informação e exibir novamente
    const statusInfo = document.querySelector('.alert.alert-light');
    if (statusInfo) {
        statusInfo.style.display = 'block';
        const infoText = statusInfo.querySelector('p.small');
        if (infoText) {
            infoText.innerHTML = 'Após criar a categoria, você pode ativar ou desativar usando o botão na tabela de categorias.';
        }
    }
    
    // Remover classe edit-mode
    const modalContent = document.querySelector('.modal-content');
    if (modalContent) {
        modalContent.classList.remove('edit-mode');
    }
}

/**
 * Processar o formulário de categoria (adicionar/editar)
 */
function saveCategoryForm(e) {
    if (e) e.preventDefault();
    
    // Criar objeto FormData com os dados do formulário
    const form = document.getElementById('categoryForm');
    if (!form) return;
    
    const formData = new FormData(form);
    
    // Verificar se estamos editando ou adicionando
    const categoryId = formData.get('category_id');
    const isEditing = categoryId && categoryId !== '';
    
    // Adicionar campo de ação
    formData.append('action', isEditing ? 'edit' : 'add');
    
    // Certifique-se de que parent_id é NULL quando estiver vazio
    const parentId = formData.get('parent_id');
    if (parentId === '') {
        formData.delete('parent_id');
        formData.append('parent_id', null);
    }
    
    // Mostrar indicador de carregamento
    const saveBtn = document.getElementById('btnSaveCategory');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Salvando...';
    }
    
    // Enviar requisição AJAX
    fetch('ajax/manage_categories.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar mensagem de sucesso
            showGlobalToast(data.message, 'success', 3000);
            
            // Fechar modal
            const modal = document.getElementById('categoryModal');
            if (modal) {
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) bootstrapModal.hide();
            }
            
            // Recarregar tabela de categorias após um breve delay
            setTimeout(() => {
                reloadCategoriesTable();
            }, 500);
        } else {
            // Mostrar erro
            showGlobalToast('Erro: ' + (data.error || 'Falha ao salvar categoria'), 'error', 5000);
            
            // Reativar botão de salvar
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = isEditing ? 
                    '<i class="fas fa-save me-2"></i> Atualizar Categoria' : 
                    '<i class="fas fa-save me-2"></i> Salvar Categoria';
            }
        }
    })
    .catch(error => {
        console.error('Erro ao salvar categoria:', error);
        showGlobalToast('Erro ao processar requisição. Verifique o console para detalhes.', 'error', 5000);
        
        // Reativar botão de salvar
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = isEditing ? 
                '<i class="fas fa-save me-2"></i> Atualizar Categoria' : 
                '<i class="fas fa-save me-2"></i> Salvar Categoria';
        }
    });
}

// Configurar eventos do modal de categoria
// Esta função garante que não haja listeners duplicados
function setupCategoryModalListeners() {
    // Referências aos elementos
    const categoryForm = document.getElementById('categoryForm');
    const categoryModal = document.getElementById('categoryModal');
    
    if (!categoryForm || !categoryModal) return;
    
    // Remover quaisquer event listeners existentes para evitar duplicação
    categoryForm.removeEventListener('submit', saveCategoryForm);
    categoryModal.removeEventListener('hidden.bs.modal', resetCategoryModal);
    
    // Adicionar novo event listener para o formulário
    categoryForm.addEventListener('submit', saveCategoryForm);
    
    // Adicionar event listener para resetar o modal quando for fechado
    categoryModal.addEventListener('hidden.bs.modal', resetCategoryModal);
    
    console.log('Event listeners para o modal de categoria configurados');
}

// Adicionar chamada ao carregar o DOM
document.addEventListener('DOMContentLoaded', function() {
    // Configurar listeners para o modal
    setupCategoryModalListeners();
    
    // Carregar a tabela de categorias
    loadCategoriesTable();
});

function toggleCategory(categoryId) {
    // Implementar lógica de toggle
    console.log('Alternando status da categoria:', categoryId);
    
    showGlobalToast('Status da categoria alterado com sucesso!', 'success');
}

function deleteCategory(categoryId) {
    console.log('Preparando para excluir categoria:', categoryId);
    
    // Mostrar indicador de carregamento
    showGlobalToast('Verificando categoria...', 'info', 1500);
    
    // Verificar primeiro se a categoria pode ser excluída
    const formData = new FormData();
    formData.append('category_id', categoryId);
    
    fetch('ajax/check_category_deletable.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const category = data.category;
            
            // Se for categoria principal com subcategorias, mostrar aviso sem opção de exclusão
            if (category.is_main_category && category.has_subcategories) {
                // Buscar informações detalhadas sobre as subcategorias
                getSubcategoriesInfo(categoryId).then(subcategoryInfo => {
                    let detailMessage;
                    
                    if (subcategoryInfo.count > 1) {
                        detailMessage = `Esta categoria possui ${subcategoryInfo.count} subcategorias que devem ser excluídas primeiro:`;
                        
                        // Se temos os nomes das subcategorias, mostrar lista
                        if (subcategoryInfo.names && subcategoryInfo.names.length > 0) {
                            detailMessage += '<ul class="mt-2 text-start">';
                            subcategoryInfo.names.forEach(name => {
                                detailMessage += `<li><i class="fas fa-angle-right me-2"></i>${name}</li>`;
                            });
                            detailMessage += '</ul>';
                        }
                    } else {
                        detailMessage = `Esta categoria possui uma subcategoria que deve ser excluída primeiro.`;
                        
                        // Se temos o nome da subcategoria, mostrar
                        if (subcategoryInfo.names && subcategoryInfo.names.length > 0) {
                            detailMessage += `<div class="mt-2 text-start ps-3"><i class="fas fa-angle-right me-2"></i>${subcategoryInfo.names[0]}</div>`;
                        }
                    }
                    
                    showWarningModal(
                        category.name,
                        'Exclusão não permitida: categoria com subcategorias',
                        detailMessage
                    );
                });
            } else {
                // Caso contrário, mostrar modal de confirmação normal
                showDeleteConfirmModal(categoryId, category.name, category.has_products);
            }
        } else {
            showGlobalToast('Erro: ' + data.error, 'error', 5000);
        }
    })
    .catch(error => {
        console.error('Erro ao verificar categoria:', error);
        showGlobalToast('Erro ao verificar a categoria. Verifique o console para mais detalhes.', 'error', 5000);
    });
}

/**
 * Mostra o modal de aviso sem opção de exclusão
 */
function showWarningModal(categoryName, mainMessage, detailMessage) {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    
    // Esconder o cabeçalho do modal completamente
    const modalHeader = modal.querySelector('.modal-header');
    if (modalHeader) {
        modalHeader.style.display = 'none';
    }
    
    // Modificar o ícone para um aviso em vez de exclusão (usar cor azul para informação)
    const iconContainer = modal.querySelector('.modal-body > div > div');
    if (iconContainer) {
        iconContainer.style.background = 'linear-gradient(135deg, #e9f5fe, #d1edff)';
        iconContainer.innerHTML = '<i class="fas fa-info-circle" style="font-size: 2.5rem; color: #3b82f6;"></i>';
    }
    
    // Configurar título e mensagens
    document.getElementById('deleteConfirmTitle').textContent = `A categoria "${categoryName}" não pode ser excluída`;
    document.getElementById('deleteConfirmMessage').textContent = mainMessage;
    
    // Alterar estilo e conteúdo do alerta de aviso para azul informativo
    const alertBox = modal.querySelector('.alert');
    if (alertBox) {
        alertBox.style.background = 'linear-gradient(135deg, #e9f5fe, #d1edff)';
        alertBox.className = 'alert alert-info border-0';
        
        // Se o detailMessage contém HTML (lista de subcategorias), use innerHTML
        if (detailMessage.includes('<ul') || detailMessage.includes('<div')) {
            alertBox.innerHTML = '<i class="fas fa-lightbulb me-2 align-self-start mt-1"></i><div id="deleteWarningMessage" class="flex-grow-1">' + detailMessage + '</div>';
            
            // Ajustar para exibição em flex para alinhar o ícone com o texto
            alertBox.style.display = 'flex';
            alertBox.style.alignItems = 'flex-start';
        } else {
            alertBox.innerHTML = '<i class="fas fa-lightbulb me-2"></i><span id="deleteWarningMessage">' + detailMessage + '</span>';
        }
    }
    
    // Ocultar botão de confirmação
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) confirmBtn.style.display = 'none';
    
    // Substituir o botão Cancelar por Entendi centralizado
    const modalFooter = modal.querySelector('.modal-footer');
    if (modalFooter) {
        // Remover todos os botões existentes
        while (modalFooter.firstChild) {
            modalFooter.removeChild(modalFooter.firstChild);
        }
        
        // Adicionar o botão Entendi estilizado
        const entendiBtn = document.createElement('button');
        entendiBtn.type = 'button';
        entendiBtn.className = 'btn btn-primary';
        entendiBtn.setAttribute('data-bs-dismiss', 'modal');
        entendiBtn.style.width = '100%';
        entendiBtn.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
        entendiBtn.style.border = 'none';
        entendiBtn.style.padding = '10px';
        entendiBtn.style.borderRadius = '8px';
        entendiBtn.innerHTML = '<i class="fas fa-check me-2"></i> Entendi';
        modalFooter.appendChild(entendiBtn);
    }
    
    // Mostrar o modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Adicionar evento para restaurar o estilo original quando o modal for fechado
    modal.addEventListener('hidden.bs.modal', resetModalStyle, { once: true });
}

/**
 * Mostra o modal de confirmação de exclusão normal
 */
function showDeleteConfirmModal(categoryId, categoryName, hasProducts) {
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    
    // Resetar estilo do modal para exclusão
    resetModalStyle();
    
    // Esconder o cabeçalho do modal completamente, seguindo o mesmo padrão do modal de aviso
    const modalHeader = modal.querySelector('.modal-header');
    if (modalHeader) {
        modalHeader.style.display = 'none';
    }
    
    // Modificar o ícone para exclusão com estilo vermelho
    const iconContainer = modal.querySelector('.modal-body > div > div');
    if (iconContainer) {
        iconContainer.style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
        iconContainer.innerHTML = '<i class="fas fa-trash-alt" style="font-size: 2.5rem; color: #dc2626;"></i>';
    }
    
    // Configurar título e mensagens
    document.getElementById('deleteConfirmTitle').textContent = `Tem certeza que deseja excluir "${categoryName}"?`;
    document.getElementById('deleteConfirmMessage').textContent = 'Esta ação não pode ser desfeita.';
    
    // Mostrar aviso sobre produtos se necessário
    const warningMessage = hasProducts 
        ? 'Esta categoria possui produtos associados que serão realocados automaticamente.'
        : 'Certifique-se de que não há dados importantes associados a esta categoria.';
    
    // Alterar estilo e conteúdo do alerta de aviso
    const alertBox = modal.querySelector('.alert');
    if (alertBox) {
        alertBox.style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
        alertBox.className = 'alert alert-warning border-0';
        alertBox.style.display = 'flex';
        alertBox.style.alignItems = 'flex-start';
        alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-2 align-self-start mt-1"></i><div id="deleteWarningMessage" class="flex-grow-1">' + warningMessage + '</div>';
    }
    
    // Configurar os botões
    const modalFooter = modal.querySelector('.modal-footer');
    if (modalFooter) {
        // Remover todos os botões existentes
        while (modalFooter.firstChild) {
            modalFooter.removeChild(modalFooter.firstChild);
        }
        
        // Adicionar botão Cancelar
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-outline-secondary';
        cancelBtn.setAttribute('data-bs-dismiss', 'modal');
        cancelBtn.innerHTML = '<i class="fas fa-times me-2"></i> Cancelar';
        modalFooter.appendChild(cancelBtn);
        
        // Adicionar botão Confirmar Exclusão
        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.id = 'confirmDeleteBtn';
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        confirmBtn.style.border = 'none';
        confirmBtn.style.padding = '10px 16px';
        confirmBtn.style.borderRadius = '8px';
        confirmBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i> Confirmar Exclusão';
        confirmBtn.onclick = confirmDeleteCategory;
        modalFooter.appendChild(confirmBtn);
        
        // Armazenar o ID da categoria a ser excluída
        document.getElementById('categoryToDeleteId').value = categoryId;
    }
    
    // Mostrar o modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
    
    // Adicionar evento para restaurar o estilo original quando o modal for fechado
    modal.addEventListener('hidden.bs.modal', resetModalStyle, { once: true });
}

/**
 * Confirma e executa a exclusão da categoria via AJAX
 */
function confirmDeleteCategory() {
    const categoryId = document.getElementById('categoryToDeleteId').value;
    if (!categoryId) return;
    
    console.log('Confirmando exclusão da categoria:', categoryId);
    
    // Desabilitar botão de confirmação para evitar cliques múltiplos
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Excluindo...';
    }
    
    // Fechar modal de confirmação após breve delay para mostrar feedback visual
    setTimeout(() => {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        }
        
        // Mostrar indicador de carregamento
        showGlobalToast('Processando exclusão...', 'info', 2000);
        
        // Enviar requisição AJAX para excluir categoria
        const formData = new FormData();
        formData.append('category_id', categoryId);
        
        fetch('ajax/delete_category.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showGlobalToast(data.message, 'success', 3000);
                // Recarregar tabela de categorias
                reloadCategoriesTable();
            } else {
                showGlobalToast('Erro: ' + data.error, 'error', 5000);
            }
        })
        .catch(error => {
            console.error('Erro ao excluir categoria:', error);
            showGlobalToast('Erro ao processar a exclusão. Verifique o console para mais detalhes.', 'error', 5000);
        });
    }, 500);
}

function updateCounters(categories) {
    let mainCount = 0;
    let subCount = 0;
    let totalCount = 0;
    
    categories.forEach(category => {
        mainCount++;
        totalCount++;
        
        if (category.subcategories) {
            subCount += category.subcategories.length;
            totalCount += category.subcategories.length;
        }
    });
    
    // Atualizar elementos dos contadores
    const mainElement = document.getElementById('mainCategoryCount');
    const subElement = document.getElementById('subCategoryCount');
    const totalElement = document.getElementById('totalCategoriesCount');
    
    if (mainElement) mainElement.textContent = mainCount;
    if (subElement) subElement.textContent = subCount;
    if (totalElement) totalElement.textContent = totalCount;
}

// Função para recarregar a tabela (útil após operações CRUD)
function reloadCategoriesTable() {
    const tableBody = document.querySelector('#categoryTable tbody');
    if (tableBody) {
        tableBody.innerHTML = `
            <tr class="loading-state">
                <td colspan="4" class="text-center">
                    <div class="loading-pulse">
                        <div class="spinner-grow" style="width: 16px; height: 16px;"></div>
                        <div class="spinner-grow" style="width: 16px; height: 16px; animation-delay: 0.2s;"></div>
                        <div class="spinner-grow" style="width: 16px; height: 16px; animation-delay: 0.4s;"></div>
                    </div>
                    <p class="mt-3 mb-0 text-muted">Carregando categorias...</p>
                </td>
            </tr>
        `;
    }
    
    loadCategoriesTable();
}

// Exportar função para uso global
window.reloadCategoriesTable = reloadCategoriesTable;

/**
 * Reseta o estilo do modal para o estado original
 * Este método é chamado quando o modal é fechado
 */
function resetModalStyle() {
    console.log('Resetando estilo do modal');
    const modal = document.getElementById('deleteConfirmModal');
    if (!modal) return;
    
    // Resetar cabeçalho - garantir que esteja presente no DOM, mas hidden para nosso novo design
    const modalHeader = modal.querySelector('.modal-header');
    if (modalHeader) {
        modalHeader.style.display = 'none'; // Mantemos oculto conforme novo design
        modalHeader.style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
        modalHeader.style.borderBottom = '1px solid rgba(239, 68, 68, 0.2)';
    }
    
    // Resetar título
    const modalTitle = document.getElementById('deleteModalLabel');
    if (modalTitle) {
        modalTitle.style.color = '#dc2626';
        modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Confirmar Exclusão';
    }
    
    // Resetar elementos do corpo do modal
    const confirmTitle = document.getElementById('deleteConfirmTitle');
    if (confirmTitle) {
        confirmTitle.textContent = 'Tem certeza que deseja excluir esta categoria?';
    }
    
    const confirmMessage = document.getElementById('deleteConfirmMessage');
    if (confirmMessage) {
        confirmMessage.textContent = 'Esta ação não pode ser desfeita.';
    }
    
    // Resetar ícone
    const iconContainer = modal.querySelector('.modal-body > div > div');
    if (iconContainer) {
        iconContainer.style.background = 'linear-gradient(135deg, #fee2e2, #fecaca)';
        iconContainer.innerHTML = '<i class="fas fa-trash-alt" style="font-size: 2.5rem; color: #dc2626;"></i>';
    }
    
    // Resetar alerta de aviso
    const alertBox = modal.querySelector('.alert');
    if (alertBox) {
        alertBox.style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
        alertBox.className = 'alert alert-warning border-0';
        alertBox.style.display = 'flex';
        alertBox.style.alignItems = 'flex-start';
        alertBox.innerHTML = '<i class="fas fa-exclamation-triangle me-2 align-self-start mt-1"></i><div id="deleteWarningMessage" class="flex-grow-1">Certifique-se de que não há dados importantes associados a esta categoria.</div>';
    }
    
    // Limpar ID da categoria selecionada
    const categoryIdField = document.getElementById('categoryToDeleteId');
    if (categoryIdField) {
        categoryIdField.value = '';
    }
    
    // Resetar botões no footer para o padrão de exclusão
    const modalFooter = modal.querySelector('.modal-footer');
    if (modalFooter) {
        // Limpar o footer
        while (modalFooter.firstChild) {
            modalFooter.removeChild(modalFooter.firstChild);
        }
        
        // Adicionar botão Cancelar
        const cancelBtn = document.createElement('button');
        cancelBtn.type = 'button';
        cancelBtn.className = 'btn btn-outline-secondary';
        cancelBtn.setAttribute('data-bs-dismiss', 'modal');
        cancelBtn.innerHTML = '<i class="fas fa-times me-2"></i> Cancelar';
        modalFooter.appendChild(cancelBtn);
        
        // Adicionar botão Confirmar Exclusão
        const confirmBtn = document.createElement('button');
        confirmBtn.type = 'button';
        confirmBtn.id = 'confirmDeleteBtn';
        confirmBtn.className = 'btn btn-danger';
        confirmBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        confirmBtn.style.border = 'none';
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i> Confirmar Exclusão';
        confirmBtn.onclick = confirmDeleteCategory;
        modalFooter.appendChild(confirmBtn);
    }
}

/**
 * Busca informações detalhadas sobre subcategorias de uma categoria
 * @param {number} categoryId ID da categoria pai
 * @returns {Promise} Promessa com informações sobre as subcategorias
 */
async function getSubcategoriesInfo(categoryId) {
    try {
        // Função para encontrar subcategorias na estrutura de dados atual
        const findSubcategoriesInTable = () => {
            // Tentar encontrar a categoria no DOM
            const categoryRow = document.querySelector(`tr[data-category-id="${categoryId}"]`);
            if (!categoryRow) return { count: 0, names: [] };
            
            // Encontrar as subcategorias seguintes
            const subRows = [];
            let nextRow = categoryRow.nextElementSibling;
            
            while (nextRow && nextRow.classList.contains('subcategory-row')) {
                subRows.push(nextRow);
                nextRow = nextRow.nextElementSibling;
            }
            
            // Extrair nomes das subcategorias
            const subcategoryNames = subRows.map(row => {
                const nameElement = row.querySelector('.subcategory-name h6');
                return nameElement ? nameElement.textContent.trim() : 'Subcategoria';
            });
            
            return {
                count: subRows.length,
                names: subcategoryNames
            };
        };
        
        // Primeiro tenta buscar da tabela atual para resposta rápida
        const tableInfo = findSubcategoriesInTable();
        if (tableInfo.count > 0) {
            return tableInfo;
        }
        
        // Se não encontrou na tabela, faz uma consulta ao servidor
        const formData = new FormData();
        formData.append('parent_id', categoryId);
        
        const response = await fetch('ajax/get_subcategories.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            return { count: 0, names: [] };
        }
        
        const data = await response.json();
        
        if (data.success && data.subcategories) {
            return {
                count: data.subcategories.length,
                names: data.subcategories.map(sub => sub.name)
            };
        }
        
        return { count: tableInfo.count > 0 ? tableInfo.count : 1, names: [] };
    } catch (error) {
        console.error('Erro ao buscar informações de subcategorias:', error);
        return { count: 1, names: [] };
    }
}
