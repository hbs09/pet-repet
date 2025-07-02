/**
 * Modern Categories JavaScript
 * Handles interaction for the modern categories UI
 */

document.addEventListener('DOMContentLoaded', function() {
    initCategoriesPage();
});

/**
 * Initialize the categories page functionality
 */
function initCategoriesPage() {
    initSearchFilter();
    initCategoryFilters();
    setupCategoryCards();
    initCategoryActions();
}

/**
 * Initialize the search functionality
 */
function initSearchFilter() {
    const searchInput = document.querySelector('.categories-search input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function(e) {
        const searchValue = e.target.value.toLowerCase().trim();
        const categoryCards = document.querySelectorAll('.category-card');
        
        categoryCards.forEach(card => {
            const categoryName = card.querySelector('.category-name').textContent.toLowerCase();
            const categoryDesc = card.querySelector('.category-description')?.textContent.toLowerCase() || '';
            
            if (categoryName.includes(searchValue) || categoryDesc.includes(searchValue)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Check if all cards are hidden, then show empty state
        checkEmptyResults();
    });
}

/**
 * Initialize category filtering functionality
 */
function initCategoryFilters() {
    const filterChips = document.querySelectorAll('.filter-chip');
    if (!filterChips.length) return;

    filterChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const filterType = this.dataset.filter;
            
            // Toggle active state
            if (this.classList.contains('active') && filterType !== 'all') {
                this.classList.remove('active');
                document.querySelector('[data-filter="all"]').classList.add('active');
            } else {
                // Remove active class from all filters
                filterChips.forEach(c => c.classList.remove('active'));
                // Add active class to clicked filter
                this.classList.add('active');
            }
            
            // Filter the categories
            filterCategories(filterType);
        });
    });
}

/**
 * Filter categories based on the selected filter
 */
function filterCategories(filterType) {
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        if (filterType === 'all') {
            card.style.display = '';
        } else {
            const cardType = card.dataset.type;
            card.style.display = (cardType === filterType) ? '' : 'none';
        }
    });
    
    // Check if all cards are hidden, then show empty state
    checkEmptyResults();
}

/**
 * Check if there are no visible results and show empty state if needed
 */
function checkEmptyResults() {
    const categoryCards = document.querySelectorAll('.category-card');
    const categoriesGrid = document.querySelector('.categories-grid');
    let visibleCards = 0;
    
    categoryCards.forEach(card => {
        if (card.style.display !== 'none') {
            visibleCards++;
        }
    });
    
    // Get or create empty state element
    let emptyState = document.querySelector('.empty-state');
    
    if (visibleCards === 0) {
        if (!emptyState) {
            emptyState = document.createElement('div');
            emptyState.className = 'empty-state';
            emptyState.innerHTML = `
                <div class="empty-state-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <div class="empty-state-text">
                    Nenhuma categoria encontrada com os filtros atuais
                </div>
                <button class="add-category-btn" onclick="openAddCategoryModal()">
                    <i class="fas fa-plus"></i> Nova Categoria
                </button>
            `;
            categoriesGrid.after(emptyState);
        }
        emptyState.style.display = '';
    } else if (emptyState) {
        emptyState.style.display = 'none';
    }
}

/**
 * Setup category cards with hover effects and interactions
 */
function setupCategoryCards() {
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        // Add click listener to make the entire card clickable
        card.addEventListener('click', function(e) {
            // Don't trigger if clicking on an action button
            if (e.target.closest('.action-btn') || e.target.closest('.category-actions')) {
                return;
            }
            
            // Navigate to category detail page
            const categoryId = this.dataset.id;
            window.location.href = `category-detail.php?id=${categoryId}`;
        });
    });
}

/**
 * Initialize category action buttons (edit, delete, etc)
 */
function initCategoryActions() {
    // Edit category action
    const editButtons = document.querySelectorAll('.action-btn.edit');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categoryId = this.closest('.category-card').dataset.id;
            openEditCategoryModal(categoryId);
        });
    });
    
    // Delete category action
    const deleteButtons = document.querySelectorAll('.action-btn.delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categoryCard = this.closest('.category-card');
            const categoryId = categoryCard.dataset.id;
            const isMainCategory = categoryCard.dataset.parent === '0';
            
            confirmDeleteModal(categoryId, isMainCategory);
        });
    });
    
    // Toggle category active/inactive
    const toggleActiveButtons = document.querySelectorAll('.action-btn.toggle-active');
    toggleActiveButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categoryId = this.closest('.category-card').dataset.id;
            toggleCategoryStatus(categoryId, 1);
        });
    });
    
    const toggleInactiveButtons = document.querySelectorAll('.action-btn.toggle-inactive');
    toggleInactiveButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categoryId = this.closest('.category-card').dataset.id;
            toggleCategoryStatus(categoryId, 0);
        });
    });
    
    // View subcategories action
    const subcategoriesButtons = document.querySelectorAll('.action-btn.subcategories');
    subcategoriesButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const categoryId = this.closest('.category-card').dataset.id;
            window.location.href = `categorias.php?parent=${categoryId}`;
        });
    });
}

/**
 * Open the add category modal
 */
function openAddCategoryModal() {
    const addModal = document.getElementById('addCategoryModal');
    if (addModal) {
        // Reset form
        const form = addModal.querySelector('form');
        if (form) form.reset();
        
        // Show modal using Bootstrap
        new bootstrap.Modal(addModal).show();
    }
}

/**
 * Open the edit category modal with category data
 */
function openEditCategoryModal(categoryId) {
    const editModal = document.getElementById('editCategoryModal');
    if (!editModal) return;
    
    // Fetch category data
    fetch(`ajax/get_category.php?id=${categoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.category) {
                // Populate form fields
                const form = editModal.querySelector('form');
                if (form) {
                    form.elements['category_id'].value = data.category.id;
                    form.elements['name'].value = data.category.name;
                    form.elements['description'].value = data.category.description || '';
                    form.elements['parent_id'].value = data.category.parent_id || '0';
                    form.elements['is_active'].checked = data.category.is_active === '1';
                    
                    // Show current image preview if exists
                    const imagePreview = editModal.querySelector('.image-preview');
                    if (imagePreview && data.category.image_url) {
                        imagePreview.innerHTML = `<img src="${data.category.image_url}" class="img-fluid rounded" alt="${data.category.name}">`;
                    }
                    
                    // Show modal using Bootstrap
                    new bootstrap.Modal(editModal).show();
                }
            } else {
                showGlobalToast('Erro ao carregar informações da categoria', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching category:', error);
            showGlobalToast('Erro ao carregar informações da categoria', 'error');
        });
}

/**
 * Toggle category active status
 * @param {number} categoryId The ID of the category
 * @param {number} status The new status (1 = active, 0 = inactive)
 */
function toggleCategoryStatus(categoryId, status) {
    // Mostrar indicador de carregamento
    showGlobalToast('Alterando status da categoria...', 'info');
    
    // Enviar requisição AJAX para alterar o status
    fetch('admin/ajax/manage_categories.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=toggle&category_id=${categoryId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showGlobalToast(data.message, 'success');
            // Recarregar a página para mostrar as alterações
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showGlobalToast(data.error || 'Erro ao alterar status da categoria', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showGlobalToast('Erro ao comunicar com o servidor', 'error');
    });
}
