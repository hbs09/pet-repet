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
                
                // Refresh the category table - DISABLED to prevent conflict with categories-table-loader.js
                // Instead, call the global reload function if available
                if (window.reloadCategoriesTable) {
                    window.reloadCategoriesTable();
                }
                // loadCategories();
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
        
       
    
        // Initialize by loading categories - DISABLED to prevent conflict with categories-table-loader.js
        // loadCategories();
        }
    })