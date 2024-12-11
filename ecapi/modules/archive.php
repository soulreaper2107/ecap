<?php
include_once "Common.php";

class Archive extends Common {
    protected $pdo;
    protected $auth;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->auth = new Authentication($pdo); // Initialize Authentication instance
    }

    // Delete a user by username (only accessible by admin)
    public function deleteUser(PDO $pdo) {
        $loggedInUserRole = $this->auth->getRole();  // Get the logged-in user's role

        if ($loggedInUserRole !== "admin") {
            return $this->generateResponse(null, "failed", "Only admins can delete users.", 403);
        }

        try {
            $username = $this->auth->getUsername();  // Get the username to delete
            $deleteUser = "DELETE FROM user_tbl WHERE username = ?";
            $stmt = $pdo->prepare($deleteUser);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $this->logger($this->auth->getUsername(), "DELETE", "User '$username' deleted successfully.");
                return $this->generateResponse(null, "success", "User '$username' deleted successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "User '$username' not found.", 404);
            }
        } catch (\Exception $e) {
            $this->logger($this->auth->getUsername(), "DELETE", "Error deleting user: " . $e->getMessage());
            return $this->generateResponse(null, "failed", "Error deleting user: " . $e->getMessage(), 500);
        }
    }

    // Delete a seller by username (only accessible by admin)
    public function deleteSeller(PDO $pdo) {
        $loggedInUserRole = $this->auth->getRole();  // Get the logged-in user's role

        if ($loggedInUserRole !== "admin") {
            return $this->generateResponse(null, "failed", "Only admins can delete sellers.", 403);
        }

        try {
            $username = $this->auth->getUsername();  // Get the username to delete
            $deleteSeller = "DELETE FROM user_tbl WHERE username = ?";
            $stmt = $pdo->prepare($deleteSeller);
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $this->logger($this->auth->getUsername(), "DELETE", "Seller '$username' deleted successfully.");
                return $this->generateResponse(null, "success", "Seller '$username' deleted successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "Seller '$username' not found.", 404);
            }
        } catch (\Exception $e) {
            $this->logger($this->auth->getUsername(), "DELETE", "Error deleting seller: " . $e->getMessage());
            return $this->generateResponse(null, "failed", "Error deleting seller: " . $e->getMessage(), 500);
        }
    }

    // Delete a product by product ID (only accessible by seller for their own products, or admin for all products)
    public function deleteProduct(PDO $pdo) {
        $loggedInUserRole = $this->auth->getRole();  // Get the logged-in user's role
        $username = $this->auth->getUsername();  // Get the logged-in user's username

        if ($loggedInUserRole === "user") {
            return $this->generateResponse(null, "failed", "Users cannot delete products.", 403);
        }

        if ($loggedInUserRole === "seller") {
            // Sellers can only delete their own products
            $sqlString = "SELECT * FROM product_tbl WHERE productid = ? AND productowner = ?";
            $stmt = $pdo->prepare($sqlString);
            $stmt->execute([$_GET['productId'], $username]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return $this->generateResponse(null, "failed", "Product not found or not owned by seller.", 404);
            }
        }

        try {
            $deleteProduct = "DELETE FROM product_tbl WHERE productid = ?";
            $stmt = $pdo->prepare($deleteProduct);
            $stmt->execute([$_GET['productId']]);

            if ($stmt->rowCount() > 0) {
                $this->logger($this->auth->getUsername(), "DELETE", "Product with ID '{$_GET['productId']}' deleted successfully.");
                return $this->generateResponse(null, "success", "Product with ID '{$_GET['productId']}' deleted successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "Product with ID '{$_GET['productId']}' not found.", 404);
            }
        } catch (\Exception $e) {
            $this->logger($this->auth->getUsername(), "DELETE", "Error deleting product: " . $e->getMessage());
            return $this->generateResponse(null, "failed", "Error deleting product: " . $e->getMessage(), 500);
        }
    }

    // Delete an item from the cart by cart ID (users can only delete their own cart items)
    public function deleteCartItem(PDO $pdo) {
        try {
            $username = $this->auth->getUsername();  // Get the logged-in user's username

            $sqlString = "SELECT * FROM cart_tbl WHERE cartid = ? AND username = ?";
            $stmt = $pdo->prepare($sqlString);
            $stmt->execute([$_GET['cartId'], $username]);
            $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cartItem) {
                return $this->generateResponse(null, "failed", "Cart item not found or not owned by user.", 404);
            }

            $deleteCartItem = "DELETE FROM cart_tbl WHERE cartid = ?";
            $stmt = $pdo->prepare($deleteCartItem);
            $stmt->execute([$_GET['cartId']]);

            if ($stmt->rowCount() > 0) {
                $this->logger($this->auth->getUsername(), "DELETE", "Cart item with ID '{$_GET['cartId']}' deleted successfully.");
                return $this->generateResponse(null, "success", "Cart item with ID '{$_GET['cartId']}' deleted successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "Cart item with ID '{$_GET['cartId']}' not found.", 404);
            }
        } catch (\Exception $e) {
            $this->logger($this->auth->getUsername(), "DELETE", "Error deleting cart item: " . $e->getMessage());
            return $this->generateResponse(null, "failed", "Error deleting cart item: " . $e->getMessage(), 500);
        }
    }
}
?>
