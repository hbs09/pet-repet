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
     * Get all main categories (parent_id IS NULL)
     * @return array Array of main categories
     */
    public function getMainCategories() {
        $stmt = $this->db->prepare("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all product types (subcategories of id=1)
     * @return array Array of product types
     */
    public function getProductTypes() {
        $stmt = $this->db->prepare("SELECT id, name, description FROM categories WHERE parent_id = 1 AND is_active = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all animal categories (subcategories of id=2)
     * @return array Array of animal categories
     */
    public function getAnimalCategories() {
        $stmt = $this->db->prepare("SELECT id, name, description FROM categories WHERE parent_id = 2 AND is_active = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all brand categories (subcategories of id=3)
     * @return array Array of brand categories
     */
    public function getBrandCategories() {
        $stmt = $this->db->prepare("SELECT id, name, description FROM categories WHERE parent_id = 3 AND is_active = TRUE ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get subcategories of a specific parent category
     * @param int $parentId Parent category ID
     * @return array Array of subcategories
     */
    public function getSubcategoriesByParentId($parentId) {
        $stmt = $this->db->prepare("SELECT id, name, description FROM categories WHERE parent_id = ? AND is_active = TRUE ORDER BY name");
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
}
?>
