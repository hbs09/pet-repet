<?php
/**
 * AJAX endpoint to get category information
 */

session_start();
require_once '../../config/database.php';
require_once '../../classes/CategoryManager.php';

// Set header to return JSON response
header('Content-Type: application/json');

// Check if user is logged in and authorized
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Category ID is required'
    ]);
    exit;
}

$category_id = (int)$_GET['id'];

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Create instance of category manager
$categoryManager = new CategoryManager($db);

// Get category information
$category = $categoryManager->getCategoryById($category_id);

if (!$category) {
    echo json_encode([
        'success' => false,
        'error' => 'Category not found'
    ]);
    exit;
}

// Return category information
echo json_encode([
    'success' => true,
    'category' => $category
]);
exit;
