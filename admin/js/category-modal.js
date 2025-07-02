/**
 * Category Modal Management
 * This script handles the category modal functionality for admin/categories.php
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const categoryForm = document.getElementById('categoryForm');
    const categoryModal = document.getElementById('categoryModal');
    const modalTitle = document.getElementById('modalTitle');
    const btnSaveCategory = document.getElementById('btnSaveCategory');
    const categoryTable = document.getElementById('categoryTable');
    
    // Form elements
    const categoryIdInput = document.getElementById('category_id');
    const categoryNameInput = document.getElementById('category_name');
    const categoryDescInput = document.getElementById('category_description');
    const categoryParentSelect = document.getElementById('parent_category');
    
    // Event listeners for opening modals
    document.getElementById('btnAddCategory').addEventListener('click', function() {
        openCategoryModal();
    });
    
    // Event listener for form submission
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveCategory();
        });
    }
    
    // Event listener para botões de editar categoria (delegação de eventos)
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-edit-category')) {
            const button = e.target.closest('.btn-edit-category');
            const categoryId = button.dataset.id;
            openCategoryModal('edit', categoryId);
        }
        
        // Event listener para botão de ativar/desativar categoria
        if (e.target.closest('.btn-toggle-status')) {
            const button = e.target.closest('.btn-toggle-status');
            const categoryId = button.dataset.id;
            const currentStatus = button.dataset.status;
            
            toggleCategoryStatus(categoryId, currentStatus);
        }
    });
    
    /**
     * Toggle the active status of a category
     * @param {string} categoryId - The category ID
     * @param {string} currentStatus - The current status of the category
     */
    function toggleCategoryStatus(categoryId, currentStatus) {
        if (!categoryId) return;
        
        // Show confirmation dialog
        const newStatus = currentStatus == 1 ? 'desativar' : 'ativar';
        if (!confirm(`Tem certeza que deseja ${newStatus} esta categoria?`)) {
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('category_id', categoryId);
        
        // Display custom paw loader
        const pawLoader = showPawLoader("Processando alteração de status...");
        
        // Send AJAX request
        fetch('ajax/manage_categories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            pawLoader.hideLoader();
            
            if (data.success) {
                // Usar cor verde para ativação e vermelha para desativação
                const isActivating = data.new_status == 1;
                
                // Usar o ToastManager centralizado para notificações padronizadas
                showGlobalToast(data.message, isActivating ? 'success' : 'error');
                
                // Refresh the category table
                loadCategories();
            } else {
                throw new Error(data.error || 'Erro ao alterar o status da categoria');
            }
        })
        .catch(error => {
            pawLoader.hideLoader();
            // Usar o ToastManager centralizado para mensagens de erro
            showGlobalToast(error.message || 'Erro ao processar solicitação', 'error');
        });
    }
    
    // Event delegation for edit buttons in the table
    if (categoryTable) {
        categoryTable.addEventListener('click', function(e) {
            // Edit button clicked
            if (e.target.classList.contains('btn-edit-category') || 
                e.target.parentElement.classList.contains('btn-edit-category')) {
                const button = e.target.classList.contains('btn-edit-category') ? 
                              e.target : 
                              e.target.parentElement;
                const categoryId = button.dataset.id;
                editCategory(categoryId);
            }
            
            // Delete buttons now use direct onclick handlers
        });
    }
    
    /**
     * Open the category modal for add/edit
     * @param {Object} categoryData - Category data for editing, null for adding
     */
    function openCategoryModal(categoryData = null) {
        // Reset form
        categoryForm.reset();
        
        if (categoryData) {
            // Edit mode
            modalTitle.textContent = 'Editar Categoria';
            categoryIdInput.value = categoryData.id;
            categoryNameInput.value = categoryData.name;
            categoryDescInput.value = categoryData.description || '';
            
            // Set parent category
            if (categoryParentSelect) {
                if (categoryData.parent_id) {
                    categoryParentSelect.value = categoryData.parent_id;
                } else {
                    categoryParentSelect.value = '';
                }
            }
            
            // Código de preview de imagem removido
            
            // Status da categoria é gerenciado apenas pelo botão de toggle
            
            btnSaveCategory.textContent = 'Atualizar Categoria';
        } else {
            // Add mode
            modalTitle.textContent = 'Adicionar Nova Categoria';
            categoryIdInput.value = '';
            btnSaveCategory.textContent = 'Adicionar Categoria';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(categoryModal);
        modal.show();
    }
    
    // Função previewImage removida
    
    
    /**
     * Save category (add or update)
     */
    function saveCategory() {
        // Create FormData object
        const formData = new FormData(categoryForm);
        
        // Add action based on whether we're editing or adding
        const isEditing = categoryIdInput.value !== '';
        formData.append('action', isEditing ? 'edit' : 'add');
        
        // Certifique-se de que parent_id é NULL quando estiver vazio
        const parentCategorySelect = document.getElementById('parent_category');
        if (parentCategorySelect && parentCategorySelect.value === '') {
            // Remover o campo parent_id do FormData (que pode conter string vazia)
            formData.delete('parent_id');
            // Adicionar explicitamente como null
            formData.append('parent_id', null);
        }
        
        console.log('Enviando category_id:', formData.get('category_id'));
        console.log('Enviando parent_id:', formData.get('parent_id'));
        
        // Send AJAX request
        fetch('../admin/ajax/manage_categories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showGlobalToast(data.message, 'success');
                
                // Close modal and reload categories
                const modal = bootstrap.Modal.getInstance(categoryModal);
                modal.hide();
                
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.error || 'Erro ao salvar categoria');
            }
        })
        .catch(error => {
            showGlobalToast(error.message, 'error');
        });
    }
    
    /**
     * Edit a category
     * @param {string} categoryId - The ID of the category to edit
     */
    function editCategory(categoryId) {
        // Create FormData
        const formData = new FormData();
        formData.append('action', 'get_category');
        formData.append('category_id', categoryId);
        
        // Send AJAX request
        fetch('../admin/ajax/manage_categories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                openCategoryModal(data.category);
            } else {
                throw new Error(data.error || 'Erro ao carregar dados da categoria');
            }
        })
        .catch(error => {
            showGlobalToast(error.message, 'error');
        });
    }
    
    /**
     * Confirm and delete a category
     * @param {string} categoryId - The ID of the category to delete
     * @param {boolean} isMain - Whether this is a main category
     */
    /**
     * As funções confirmDeleteCategory, showDeleteConfirmationModal e deleteCategory foram removidas
     * para evitar duplicação com a função confirmDeleteModal() em categories-script.js
     * Isso resolve o problema de notificações duplicadas ao eliminar categorias.
     * Toda a funcionalidade de exclusão foi consolidada em confirmDeleteModal() no arquivo categories-script.js
     */
    
    // Load categories for initial display
    function loadCategories() {
        // Only if we have a category table
        if (!categoryTable) return;
        
        // Create FormData
        const formData = new FormData();
        formData.append('action', 'get_categories');
        
        // Send AJAX request
        fetch('../admin/ajax/manage_categories.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCategoryTable(data.categories);
            } else {
                throw new Error(data.error || 'Erro ao carregar categorias');
            }
        })
        .catch(error => {
            showGlobalToast('Falha ao carregar categorias: ' + error.message, 'error');
        });
    }
    
    /**
     * Render the category table with data
     * @param {Array} categories - Array of category objects
     */
    function renderCategoryTable(categories) {
        if (!categoryTable || !categories) return;
        
        const tbody = categoryTable.querySelector('tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (categories.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center">Nenhuma categoria encontrada</td></tr>`;
            return;
        }
        
        // Group categories by parent
        const mainCategories = categories.filter(cat => cat.parent_id === null);
        
        // Sort main categories
        mainCategories.sort((a, b) => a.name.localeCompare(b.name));
        
        // Render each main category and its subcategories
        mainCategories.forEach(mainCat => {
            // Render main category
            renderCategoryRow(tbody, mainCat, true);
            
            // Find and render subcategories
            const subcategories = categories.filter(cat => cat.parent_id === mainCat.id);
            subcategories.sort((a, b) => a.name.localeCompare(b.name));
            
            subcategories.forEach(subCat => {
                renderCategoryRow(tbody, subCat, false);
            });
        });
    }
    
    /**
     * Render a single category table row
     * @param {HTMLElement} tbody - The table body element
     * @param {Object} category - The category object
     * @param {boolean} isMain - Whether this is a main category
     */
    function renderCategoryRow(tbody, category, isMain) {
        const row = document.createElement('tr');
        
        // Add appropriate class for main/sub categories
        if (isMain) {
            row.classList.add('table-primary');
        } else {
            row.classList.add('category-child');
        }
        
        // Build row content with modern design focused on icons
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <i class="fas ${isMain ? 'fa-folder' : 'fa-level-down-alt'}"></i>
                    <span class="fw-${isMain ? 'bold' : 'normal'}">${category.name}</span>
                </div>
                ${category.description ? `<span class="d-none d-md-inline small text-muted">${category.description.substring(0, 50) + (category.description.length > 50 ? '...' : '')}</span>` : ''}
            </td>
            <td class="text-center">
                <div class="d-flex flex-column align-items-center">
                    <div class="mb-1" title="${category.subcategory_count || 0} subcategorias">
                        <i class="fas fa-sitemap"></i>
                        <span class="d-none d-md-inline-block">${category.subcategory_count || 0}</span>
                    </div>
                    <div title="${category.product_count || 0} produtos">
                        <i class="fas fa-box"></i>
                        <span class="d-none d-md-inline-block">${category.product_count || 0}</span>
                    </div>
                </div>
            </td>
            <td class="text-center">
                <i class="fas ${category.is_active == 1 ? 'fa-toggle-on text-success' : 'fa-toggle-off text-danger'}" 
                   title="${category.is_active == 1 ? 'Ativo' : 'Inativo'}"></i>
            </td>
            <td class="text-center">
                <div class="btn-group">
                    <button class="btn btn-action btn-edit btn-edit-category" data-id="${category.id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-action ${category.is_active == 1 ? 'btn-warning' : 'btn-success'} btn-toggle-status" 
                        data-id="${category.id}" 
                        data-status="${category.is_active}"
                        title="${category.is_active == 1 ? 'Desativar' : 'Ativar'}">
                        <i class="fas ${category.is_active == 1 ? 'fa-power-off' : 'fa-check'}"></i>
                    </button>
                    ${parseInt(category.id) > 3 ? `
                        <button class="btn btn-action btn-delete btn-delete-category" 
                            data-id="${category.id}" 
                            data-is-main="${isMain}"
                            onclick="confirmDeleteModal(${category.id}, ${isMain})"
                            title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        
        tbody.appendChild(row);
    }
    
    // Initialize by loading categories
    loadCategories();
});
