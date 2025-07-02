<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

// Verificar se o utilizador está logado e é administrador
if(!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../login.php");
    exit;
}

// Verificar se o formulário foi enviado
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Conectar ao banco de dados
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Iniciar transação
        $db->beginTransaction();
        
        // Validar dados obrigatórios
        $required_fields = ['name', 'price', 'category_id', 'stock_quantity'];
        foreach($required_fields as $field) {
            if(empty($_POST[$field])) {
                throw new Exception("O campo " . $field . " é obrigatório.");
            }
        }
        
        // Processar imagem se foi enviada
        $image_url = null;
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if(!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou WEBP.");
            }
            
            if($_FILES['image']['size'] > $max_size) {
                throw new Exception("A imagem deve ter no máximo 2MB.");
            }
            
            // Criar diretório se não existir
            $upload_dir = '../media/produtos/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = './media/produtos/' . $new_filename;
            } else {
                throw new Exception("Falha ao fazer upload da imagem.");
            }
        }
        
        // Inserir produto
        $query = "INSERT INTO products (name, description, short_description, price, sale_price, 
                 sku, image_url, category_id, brand, stock_quantity, is_active, is_featured) 
                 VALUES (:name, :description, :short_description, :price, :sale_price, 
                 :sku, :image_url, :category_id, :brand, :stock_quantity, :is_active, :is_featured)";
        
        $stmt = $db->prepare($query);
        
        // Bind dos parâmetros
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':description', $_POST['description']);
        $stmt->bindParam(':short_description', $_POST['short_description']);
        $stmt->bindParam(':price', $_POST['price']);
        
        $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : null;
        $stmt->bindParam(':sale_price', $sale_price);
        
        $stmt->bindParam(':sku', $_POST['sku']);
        $stmt->bindParam(':image_url', $image_url);
        $stmt->bindParam(':category_id', $_POST['category_id']);
        $stmt->bindParam(':brand', $_POST['brand']);
        $stmt->bindParam(':stock_quantity', $_POST['stock_quantity']);
        $stmt->bindParam(':is_active', $_POST['is_active']);
        $stmt->bindParam(':is_featured', $_POST['is_featured']);
        
        if($stmt->execute()) {
            $product_id = $db->lastInsertId();
            
            // Processar categorias adicionais
            if(isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                $categories_query = "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)";
                $cat_stmt = $db->prepare($categories_query);
                
                foreach($_POST['additional_categories'] as $category_id) {
                    $cat_stmt->bindParam(':product_id', $product_id);
                    $cat_stmt->bindParam(':category_id', $category_id);
                    $cat_stmt->execute();
                }
            }
            
            // Adicionar categoria principal também (se não estiver já nas adicionais)
            $check_query = "SELECT COUNT(*) FROM product_categories WHERE product_id = :product_id AND category_id = :category_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':product_id', $product_id);
            $check_stmt->bindParam(':category_id', $_POST['category_id']);
            $check_stmt->execute();
            
            if($check_stmt->fetchColumn() == 0) {
                $main_cat_query = "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)";
                $main_cat_stmt = $db->prepare($main_cat_query);
                $main_cat_stmt->bindParam(':product_id', $product_id);
                $main_cat_stmt->bindParam(':category_id', $_POST['category_id']);
                $main_cat_stmt->execute();
            }
            
            // Adicionar imagem principal à tabela product_images
            if($image_url) {
                $image_query = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary) 
                               VALUES (:product_id, :image_url, :alt_text, 1)";
                $img_stmt = $db->prepare($image_query);
                $img_stmt->bindParam(':product_id', $product_id);
                $img_stmt->bindParam(':image_url', $image_url);
                $img_stmt->bindParam(':alt_text', $_POST['name']);
                $img_stmt->execute();
            }
            
            $db->commit();
            $_SESSION['message'] = "Produto adicionado com sucesso.";
            $_SESSION['message_type'] = "success";
        } else {
            throw new Exception("Erro ao adicionar produto.");
        }
        
        header("Location: products.php");
        exit;
        
    } catch(Exception $e) {
        $db->rollBack();
        $_SESSION['message'] = "Erro: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: products.php");
        exit;
    }
}

// Se não for POST, redirecionar para a página de produtos
header("Location: products.php");
exit;
