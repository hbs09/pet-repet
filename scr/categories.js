document.addEventListener('DOMContentLoaded', function() {
    // Carregar categorias dinamicamente quando o mouse passar sobre o item do menu
    const productMenuItem = document.querySelector('.nav-item.dropdown-item');
    
    if (productMenuItem) {
        productMenuItem.addEventListener('mouseenter', function() {
            console.log('Menu de categorias carregado');
        }, { once: true }); // Carregar apenas uma vez
    }
    
    // Função para criar coluna de categoria
    function createCategoryColumn(category) {
        const col = document.createElement('div');
        col.className = 'dropdown-col category-section';
        col.innerHTML = `
            <div class="col-header">
                <div class="main-category">
                    <a href="categoria.php?parent=${category.id}" class="category-title">
                        ${category.name}
                    </a>
                </div>
            </div>
            <div class="col-content">
                <ul class="dropdown-list" id="subcategories-${category.id}">
                </ul>
            </div>
        `;
        
        // Carregar subcategorias
        loadSubcategories(category.id, col.querySelector(`#subcategories-${category.id}`));
        
        return col;
    }

    // Função para carregar subcategorias via AJAX
    function loadSubcategories(parentId, container) {
        // Indicador de carregamento
        container.innerHTML = '<li><div class="loading">Carregando...</div></li>';
        
        fetch(`ajax/get_categories.php?parent=${parentId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede');
                }
                return response.json();
            })
            .then(data => {
                if (data.categories && data.categories.length > 0) {
                    container.innerHTML = '';
                    
                    // Adiciona cada subcategoria à lista
                    data.categories.forEach(subcat => {
                        const li = document.createElement('li');
                        li.innerHTML = `<a href="categoria.php?id=${subcat.id}" class="dropdown-link">${subcat.name}</a>`;
                        container.appendChild(li);
                    });
                } else {
                    container.innerHTML = '<li><div class="no-subcats">Nenhuma subcategoria encontrada</div></li>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar subcategorias:', error);
                container.innerHTML = '<li><div class="error">Erro ao carregar subcategorias</div></li>';
            });
    }

    // Expor funções globalmente se necessário
    window.createCategoryColumn = createCategoryColumn;
    window.loadSubcategories = loadSubcategories;
});
