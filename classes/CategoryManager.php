<?php
/**
 * CategoryManager Class
 * Responsible for handling category operations and relationships
 */
class CategoryManager {
    private $db;
    
    /**
     * Constructor
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all categories with detailed information
     * @return array Array of all categories with details
     */
    public function getAllCategoriesWithDetails() {
        $query = "SELECT c.*, p.name as parent_name, 
                 (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count,
                 (SELECT COUNT(*) FROM product_categories WHERE category_id = c.id) as product_count
                 FROM categories c
                 LEFT JOIN categories p ON c.parent_id = p.id
                 ORDER BY COALESCE(c.parent_id, c.id), c.name";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all main categories (parent_id IS NULL)
     * @return array Array of main categories
     */
    public function getMainCategories() {
        $stmt = $this->db->prepare("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all product types (subcategories where parent category name contains "Tipo de produto")
     * @return array Array of product types
     */
    public function getProductTypes() {
        try {
            // Primeiro tenta localizar pela correspondência exata
            $stmt = $this->db->prepare("
                SELECT c.id, c.name, c.description 
                FROM categories c
                JOIN categories p ON c.parent_id = p.id
                WHERE LOWER(p.name) = LOWER('Tipo de produto')
                ORDER BY c.name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se não encontrou nada, tenta por correspondência parcial
            if (empty($result)) {
                $stmt = $this->db->prepare("
                    SELECT c.id, c.name, c.description 
                    FROM categories c
                    JOIN categories p ON c.parent_id = p.id
                    WHERE LOWER(p.name) LIKE LOWER('%tipo%produto%')
                    ORDER BY c.name
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar tipos de produtos: " . $e->getMessage());
            return []; // Retorna array vazio em caso de erro
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all animal categories (subcategories where parent category name contains "Animal")
     * @return array Array of animal categories
     */
    public function getAnimalCategories() {
        try {
            // Primeiro tenta localizar pela correspondência exata
            $stmt = $this->db->prepare("
                SELECT c.id, c.name, c.description 
                FROM categories c
                JOIN categories p ON c.parent_id = p.id
                WHERE LOWER(p.name) = LOWER('Animal')
                ORDER BY c.name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se não encontrou nada, tenta por correspondência parcial
            if (empty($result)) {
                $stmt = $this->db->prepare("
                    SELECT c.id, c.name, c.description 
                    FROM categories c
                    JOIN categories p ON c.parent_id = p.id
                    WHERE LOWER(p.name) LIKE LOWER('%animal%')
                    ORDER BY c.name
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias de animais: " . $e->getMessage());
            return []; // Retorna array vazio em caso de erro
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all brand categories (subcategories where parent category name contains "Marca")
     * @return array Array of brand categories
     */
    public function getBrandCategories() {
        try {
            // Primeiro tenta localizar pela correspondência exata
            $stmt = $this->db->prepare("
                SELECT c.id, c.name, c.description 
                FROM categories c
                JOIN categories p ON c.parent_id = p.id
                WHERE LOWER(p.name) = LOWER('Marca')
                ORDER BY c.name
            ");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Se não encontrou nada, tenta por correspondência parcial
            if (empty($result)) {
                $stmt = $this->db->prepare("
                    SELECT c.id, c.name, c.description 
                    FROM categories c
                    JOIN categories p ON c.parent_id = p.id
                    WHERE LOWER(p.name) LIKE LOWER('%marca%')
                    ORDER BY c.name
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erro ao buscar categorias de marcas: " . $e->getMessage());
            return []; // Retorna array vazio em caso de erro
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subcategories of a specific parent category
     * @param int $parentId Parent category ID
     * @return array Array of subcategories
     */
    public function getSubcategoriesByParentId($parentId) {
        $stmt = $this->db->prepare("SELECT id, name, description, image_url, is_active FROM categories WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get products by category
     * @param int $categoryId Category ID
     * @param int $limit Number of products to return
     * @return array Array of products in the category
     */
    public function getProductsByCategory($categoryId, $limit = 10) {
        $stmt = $this->db->prepare("
            SELECT p.* FROM products p 
            JOIN product_categories pc ON p.id = pc.product_id 
            WHERE pc.category_id = ? AND p.is_active = TRUE 
            ORDER BY p.name LIMIT ?
        ");
        $stmt->bindParam(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new category
     * @param string $name Category name
     * @param string $description Category description
     * @param int|null $parentId Parent category ID
     * @param bool $isActive Whether the category is active (always true by default)
     * @return int|bool ID of the new category or false on failure
     */
    public function createCategory($name, $description, $parentId = null, $isActive = true) {
        try {
            // Check if name already exists for the same parent level
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND parent_id " . 
                                       ($parentId === null ? "IS NULL" : "= ?"));
            if ($parentId === null) {
                $stmt->execute([$name]);
            } else {
                $stmt->execute([$name, $parentId]);
            }
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Uma categoria com este nome já existe neste nível");
            }
            
            // Verificar se a categoria pai existe, se foi especificada
            if ($parentId !== null) {
                $checkParent = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
                $checkParent->execute([$parentId]);
                if ($checkParent->fetchColumn() == 0) {
                    // Se a categoria pai não existir, definimos como categoria principal
                    error_log("Tentativa de criar categoria com parent_id $parentId, mas o pai não existe. Definindo como categoria principal.");
                    $parentId = null;
                }
            }

            try {
                $query = "INSERT INTO categories (name, description, parent_id, is_active) VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([$name, $description, $parentId, 1]); // Sempre criar como ativa
                
                if ($result) {
                    return $this->db->lastInsertId();
                }
                return false;
            } catch (PDOException $pdoe) {
                // Se for erro de constraint (categoria pai não existe), definir como categoria principal
                if ($pdoe->getCode() == '23000' && strpos($pdoe->getMessage(), 'categories_ibfk_1') !== false) {
                    error_log("Erro de constraint ao criar categoria. Tentando criar como categoria principal.");
                    $query = "INSERT INTO categories (name, description, parent_id, is_active) VALUES (?, ?, NULL, ?)";
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([$name, $description, 1]); // Sempre criar como ativa
                    
                    if ($result) {
                        return $this->db->lastInsertId();
                    }
                }
                
                error_log("PDO Exception ao criar categoria: " . $pdoe->getMessage() . " - Code: " . $pdoe->getCode());
                throw new Exception("Erro no banco de dados ao criar categoria: " . $pdoe->getMessage());
            }
        } catch (Exception $e) {
            error_log("Error creating category: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update an existing category
     * @param int $id Category ID
     * @param string $name Category name
     * @param string $description Category description
     * @param string|null $imageUrl URL of the category image
     * @param int|null $parentId Parent category ID
     * @param bool $isActive Whether the category is active
     * @return bool Success or failure
     */
    public function updateCategory($id, $name, $description = null, $parentId = null, $isActive = null) {
        try {
            // First get the existing category
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $existingCategory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingCategory) {
                throw new Exception("Categoria não encontrada");
            }
            
            // Check if name already exists for another category at the same parent level
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND parent_id " . 
                                      ($parentId === null ? "IS NULL" : "= ?") . " AND id != ?");
            if ($parentId === null) {
                $stmt->execute([$name, $id]);
            } else {
                $stmt->execute([$name, $parentId, $id]);
            }
            
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Já existe outra categoria com este nome neste nível");
            }
            
            // Use existing values if not provided
            $description = $description !== null ? $description : $existingCategory['description'];
            $parentId = $parentId !== null ? $parentId : $existingCategory['parent_id'];
            $isActive = $isActive !== null ? ($isActive ? 1 : 0) : $existingCategory['is_active'];
            
            // Verificar se a categoria está tentando ser sua própria pai
            if ($parentId !== null && $parentId == $id) {
                throw new Exception("Uma categoria não pode ser pai de si mesma");
            }
            
            // Verificar se a categoria é uma categoria principal que tem subcategorias
            if ($existingCategory['parent_id'] === null) {
                $checkSubs = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
                $checkSubs->execute([$id]);
                $hasSubcategories = ($checkSubs->fetchColumn() > 0);
                
                // Se estamos tentando alterar uma categoria principal para subcategoria e ela tem filhos
                if ($parentId !== null && $hasSubcategories) {
                    throw new Exception("Não é possível converter uma categoria principal em subcategoria quando ela possui subcategorias.");
                }
            }
            
            // Verificar se o pai existe, se foi especificado
            if ($parentId !== null) {
                $checkParent = $this->db->prepare("SELECT id FROM categories WHERE id = ?");
                $checkParent->execute([$parentId]);
                if (!$checkParent->fetch()) {
                    error_log("Tentativa de atualizar categoria ID $id com parent_id $parentId, mas o pai não existe");
                    // Se o parent_id especificado não existe, definimos como NULL (categoria principal)
                    $parentId = null;
                    throw new Exception("A categoria pai especificada não existe ou foi removida. A categoria será definida como categoria principal.");
                }
                
                // Verificar se há ciclo na hierarquia
                if ($this->wouldCreateCycle($id, $parentId)) {
                    throw new Exception("Esta alteração criaria um ciclo na hierarquia de categorias");
                }
            }
            
            try {
                $query = "UPDATE categories SET name = ?, description = ?, parent_id = ?, is_active = ? WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([$name, $description, $parentId, $isActive, $id]);
                
                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("Erro SQL ao atualizar categoria: " . print_r($errorInfo, true));
                    throw new Exception("Erro ao atualizar categoria: " . $errorInfo[2]);
                }
                
                return $result;
            } catch (PDOException $pdoe) {
                // Se for erro de constraint (categoria pai não existe), definir como categoria principal
                if ($pdoe->getCode() == '23000' && strpos($pdoe->getMessage(), 'categories_ibfk_1') !== false) {
                    error_log("Erro de constraint ao atualizar categoria. Definindo como categoria principal.");
                    $query = "UPDATE categories SET name = ?, description = ?, parent_id = NULL, is_active = ? WHERE id = ?";
                    $stmt = $this->db->prepare($query);
                    $result = $stmt->execute([$name, $description, $isActive, $id]);
                    
                    if ($result) {
                        return $result;
                    }
                }
                
                error_log("PDO Exception ao atualizar categoria: " . $pdoe->getMessage() . " - Code: " . $pdoe->getCode());
                throw new Exception("Erro no banco de dados ao atualizar categoria: " . $pdoe->getMessage());
            }
        } catch (Exception $e) {
            error_log("Error updating category: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Delete a category
     * @param int $id Category ID
     * @return bool Success or failure
     */
    public function deleteCategory($id) {
        try {
            // Check if category has subcategories
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Não é possível excluir esta categoria pois ela possui subcategorias");
            }
            
            // Begin transaction to ensure data consistency
            $this->db->beginTransaction();
            
            // Check for products directly associated with this category
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            $hasProducts = $stmt->fetchColumn() > 0;
            
            if ($hasProducts) {
                // Find an alternative category for the products
                // For simplicity, we'll use the same logic as in the categories.php page
                
                // Get parent_id of the category being deleted
                $stmt = $this->db->prepare("SELECT parent_id FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $parentId = $stmt->fetchColumn();
                
                // Find alternative category
                $defaultCategory = null;
                if ($parentId === null) {
                    // For main categories, use another main category
                    $stmt = $this->db->prepare("SELECT id FROM categories WHERE parent_id IS NULL AND id != ? LIMIT 1");
                    $stmt->execute([$id]);
                    $defaultCategory = $stmt->fetchColumn();
                    
                    if (!$defaultCategory) {
                        // Se não houver outra categoria principal, criar uma temporária
                        $this->ensureDefaultCategoryStructure();
                        $stmt = $this->db->prepare("SELECT id FROM categories WHERE parent_id IS NULL AND id != ? LIMIT 1");
                        $stmt->execute([$id]);
                        $defaultCategory = $stmt->fetchColumn();
                    }
                } else {
                    // For subcategories, use another subcategory of the same parent
                    $stmt = $this->db->prepare("SELECT id FROM categories WHERE parent_id = ? AND id != ? LIMIT 1");
                    $stmt->execute([$parentId, $id]);
                    $defaultCategory = $stmt->fetchColumn();
                    
                    if (!$defaultCategory) {
                        // If no other subcategory, use the parent
                        $defaultCategory = $parentId;
                    }
                }
                
                // Move products to the alternative category
                $stmt = $this->db->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
                $stmt->execute([$defaultCategory, $id]);
            }
            
            // Remove associations in product_categories table
            $stmt = $this->db->prepare("DELETE FROM product_categories WHERE category_id = ?");
            $stmt->execute([$id]);
            
            // Remove the category
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error deleting category: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Os métodos que estavam nesta seção foram removidos porque já existiam na classe
    
    /**
     * Count products in a category (including products in subcategories)
     * @param int $categoryId Category ID
     * @return int Number of products
     */
    public function countProductsInCategory($categoryId) {
        // Count products directly associated with this category
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT p.id) FROM products p
            JOIN product_categories pc ON p.id = pc.product_id
            WHERE pc.category_id = ?
        ");
        $stmt->execute([$categoryId]);
        $directCount = $stmt->fetchColumn();
        
        // Count products associated with subcategories
        $subCategories = $this->getSubcategoriesByParentId($categoryId);
        $subCount = 0;
        
        foreach ($subCategories as $subCat) {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT p.id) FROM products p
                JOIN product_categories pc ON p.id = pc.product_id
                WHERE pc.category_id = ?
            ");
            $stmt->execute([$subCat['id']]);
            $subCount += $stmt->fetchColumn();
        }
        
        return $directCount + $subCount;
    }
    
    /**
     * Check if a category has related products
     * @param int $categoryId Category ID
     * @return bool True if has products, false otherwise
     */
    public function categoryHasProducts($categoryId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM products 
            WHERE category_id = ? 
            UNION 
            SELECT COUNT(*) FROM product_categories 
            WHERE category_id = ?
        ");
        $stmt->execute([$categoryId, $categoryId]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        
        return ($result > 0);
    }
    
    /**
     * Check if main categories structure exists
     * @return bool True if the main categories structure exists
     */
    public function mainCategoriesStructureExists() {
        // Verificar se existem categorias principais para Tipo de produto, Animal e Marca
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM categories 
            WHERE parent_id IS NULL 
            AND (LOWER(name) = LOWER('Tipo de produto') OR LOWER(name) = LOWER('Animal') OR LOWER(name) = LOWER('Marca'))
        ");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count >= 3; // Verificar se existem pelo menos as 3 categorias principais
    }
    
    /**
     * Create default category structure if none exists
     * @return bool True if structure was created or already exists
     */
    public function ensureDefaultCategoryStructure() {
        // Se a estrutura já existe, não faz nada
        if ($this->mainCategoriesStructureExists()) {
            return true;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Criar categorias principais
            $mainCategories = [
                'Tipo de produto' => 'Classificação por tipo de produto',
                'Animal' => 'Classificação por animal de destino',
                'Marca' => 'Classificação por marca do produto'
            ];
            
            foreach ($mainCategories as $name => $description) {
                $stmt = $this->db->prepare("
                    INSERT INTO categories (name, description, parent_id, is_active) 
                    VALUES (?, ?, NULL, 1)
                ");
                $stmt->execute([$name, $description]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erro ao criar estrutura de categorias: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Toggle a category's active status
     * @param int $id Category ID
     * @param int $status New status (1 for active, 0 for inactive)
     * @return bool Success or failure
     */
    public function toggleCategoryStatus($id, $status) {
        try {
            $status = (int)$status; // Assegurar que é 0 ou 1
            if ($status !== 0 && $status !== 1) {
                throw new Exception("Status inválido. Deve ser 0 ou 1.");
            }
            
            // Verificar se a categoria existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("Categoria não encontrada");
            }
            
            // Atualizar o status
            $stmt = $this->db->prepare("UPDATE categories SET is_active = ? WHERE id = ?");
            $result = $stmt->execute([$status, $id]);
            
            if (!$result) {
                throw new Exception("Erro ao atualizar o status da categoria");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error toggling category status: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get categories by parent ID with detailed information including product count
     * @param int $parent_id Parent category ID (0 for main categories)
     * @return array Array of categories with the specified parent ID
     */
    public function getCategoriesByParent($parent_id = 0) {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count,
                 (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
                 FROM categories c
                 WHERE " . ($parent_id > 0 ? "c.parent_id = ?" : "c.parent_id IS NULL OR c.parent_id = 0") . "
                 ORDER BY c.name";
        
        $stmt = $this->db->prepare($query);
        
        if ($parent_id > 0) {
            $stmt->bindParam(1, $parent_id);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get category by ID
     * @param int $categoryId Category ID
     * @return array|bool Category data or false if not found
     */
    public function getCategoryById($categoryId) {
        try {
            // Consulta principal para obter os dados básicos da categoria
            $query = "SELECT c.*, p.name as parent_name,
                     (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as subcategory_count,
                     (SELECT COUNT(*) FROM product_categories WHERE category_id = c.id) as product_count
                     FROM categories c
                     LEFT JOIN categories p ON c.parent_id = p.id
                     WHERE c.id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$categoryId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                return false;
            }
            
            // Verificar se tem subcategorias
            $category['has_subcategories'] = ($category['subcategory_count'] > 0);
            
            // Verificar se tem produtos associados
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM product_categories WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $category['has_products'] = ($stmt->fetchColumn() > 0);
            
            return $category;
        } catch (Exception $e) {
            error_log("Error getting category by ID: " . $e->getMessage());
            return false;
        }
    }
    

    
    /**
     * Verifica se definir parentId como pai de categoryId criaria um ciclo na hierarquia
     * 
     * @param int $categoryId ID da categoria que está sendo editada
     * @param int $parentId ID da categoria pai proposta
     * @return bool True se criaria um ciclo, False caso contrário
     */
    private function wouldCreateCycle($categoryId, $parentId) {
        if ($categoryId == $parentId) {
            return true; // Uma categoria não pode ser pai de si mesma
        }
        
        // Verificar ancestrais recursivamente
        $currentParentId = $parentId;
        $visitedIds = [$categoryId];
        
        while ($currentParentId !== null) {
            if (in_array($currentParentId, $visitedIds)) {
                return true; // Encontrou um ciclo
            }
            
            $visitedIds[] = $currentParentId;
            
            // Buscar o pai do pai atual
            $stmt = $this->db->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $stmt->execute([$currentParentId]);
            $currentParentId = $stmt->fetchColumn();
            
            // Limitar a profundidade para evitar loop infinito em caso de problemas no banco
            if (count($visitedIds) > 100) {
                error_log("Possível ciclo de hierarquia detectado ou profundidade excessiva na árvore de categorias");
                return true;
            }
        }
        
        return false;
    }
}
?>
