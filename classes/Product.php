<?php
class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $name;
    public $description;
    public $price;
    public $sale_price;
    public $image_url;
    public $category_id;
    public $brand;
    public $stock_quantity;
    public $is_active;
    public $is_featured;

    public function __construct($db) {
        $this->conn = $db;
    }

    function readFeatured() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_featured = 1 AND p.is_active = 1 AND p.stock_quantity > 0
                  ORDER BY p.created_at DESC
                  LIMIT 6";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function readByCategory($category_id) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.category_id = ? AND p.is_active = 1 AND p.stock_quantity > 0
                  ORDER BY p.name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $category_id);
        $stmt->execute();
        return $stmt;
    }

    function readAll() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1 AND p.stock_quantity > 0
                  ORDER BY p.name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    function readOne() {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = ? AND p.is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->sale_price = $row['sale_price'];
            $this->image_url = $row['image_url'];
            $this->category_id = $row['category_id'];
            $this->brand = $row['brand'];
            $this->stock_quantity = $row['stock_quantity'];
            return true;
        }
        return false;
    }

    function search($keywords) {
        $query = "SELECT p.*, c.name as category_name 
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE (p.name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?) 
                  AND p.is_active = 1 AND p.stock_quantity > 0
                  ORDER BY p.name";

        $stmt = $this->conn->prepare($query);
        $keywords = "%{$keywords}%";
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->execute();
        return $stmt;
    }
}
?>
