<?php

// Import dependencies
require_once "./config/database.php";

class Checkout {
    protected $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Method to get cart items and calculate totals
    public function getCartDetails($username) {
        try {
            // Fetch all cart items for the user
            $sql = "SELECT productname, productprize, quantity 
                    FROM cart_tbl 
                    WHERE username = :username";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $cartItems = $stmt->fetchAll();

            if (!$cartItems) {
                return [
                    "status" => "failed",
                    "message" => "No items found in the cart.",
                    "payload" => null
                ];
            }

            // Calculate total quantity and price
            $totalQuantity = 0;
            $totalPrice = 0;
            foreach ($cartItems as $item) {
                $totalQuantity += $item['quantity'];
                $totalPrice += $item['productprize'] * $item['quantity'];
            }

            // Return the cart details
            return [
                "status" => "success",
                "message" => "Cart details retrieved successfully.",
                "payload" => [
                    "items" => $cartItems,
                    "total_quantity" => $totalQuantity,
                    "total_price" => $totalPrice
                ]
            ];

        } catch (Exception $e) {
            return [
                "status" => "failed",
                "message" => $e->getMessage(),
                "payload" => null
            ];
        }
    }
}

// Initialize the database connection
$db = new Connection();
$pdo = $db->connect();

// Handle the GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['username'])) {
        $username = $_GET['username'];
        $checkout = new Checkout($pdo);
        $response = $checkout->getCartDetails($username);
        
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "failed",
            "message" => "Username is required."
        ], JSON_PRETTY_PRINT);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "failed",
        "message" => "Invalid request method."
    ], JSON_PRETTY_PRINT);
}

?>
