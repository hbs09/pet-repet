<?php
class Cart {
    private $conn;
    private $table_name = "shopping_cart";

    public function __construct($db) {
        $this->conn = $db;
    }

    function addItem($user_id, $session_id, $product_id, $quantity) {
        // Check if item already exists
        $check_query = "SELECT id, quantity FROM " . $this->table_name . " 
                       WHERE (user_id = ? OR session_id = ?) AND product_id = ?";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(1, $user_id);
        $check_stmt->bindParam(2, $session_id);
        $check_stmt->bindParam(3, $product_id);
        $check_stmt->execute();

        if($check_stmt->rowCount() > 0) {
            // Update quantity
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_quantity = $row['quantity'] + $quantity;
            
            $update_query = "UPDATE " . $this->table_name . " 
                           SET quantity = ?, updated_at = NOW() 
                           WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(1, $new_quantity);
            $update_stmt->bindParam(2, $row['id']);
            return $update_stmt->execute();
        } else {
            // Insert new item
            $query = "INSERT INTO " . $this->table_name . " 
                     (user_id, session_id, product_id, quantity) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $user_id);
            $stmt->bindParam(2, $session_id);
            $stmt->bindParam(3, $product_id);
            $stmt->bindParam(4, $quantity);
            return $stmt->execute();
        }
    }

    function getItems($user_id, $session_id) {
        $query = "SELECT c.*, p.name, p.price, p.sale_price, p.image_url, p.stock_quantity
                  FROM " . $this->table_name . " c
                  JOIN products p ON c.product_id = p.id
                  WHERE (c.user_id = ? OR c.session_id = ?) AND p.is_active = 1
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $session_id);
        $stmt->execute();
        return $stmt;
    }

    function getItemCount($user_id, $session_id) {
        $query = "SELECT SUM(quantity) as total FROM " . $this->table_name . " 
                  WHERE (user_id = ? OR session_id = ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $session_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ? $row['total'] : 0;
    }

    function updateQuantity($cart_id, $quantity) {
        $query = "UPDATE " . $this->table_name . " 
                  SET quantity = ?, updated_at = NOW() 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $quantity);
        $stmt->bindParam(2, $cart_id);
        return $stmt->execute();
    }

    function removeItem($cart_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $cart_id);
        return $stmt->execute();
    }
}
?>
