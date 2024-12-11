<?php

include_once "Common.php";
include_once "Auth.php";

class Patch extends Common {

    protected $pdo;
    protected $auth;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->auth = new Authentication($pdo); // Initialize Authentication instance
    }

    // Update user data (role handling included)
    public function updateUser($id, $body, $userRole) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->username) && empty($body->password) && empty($body->email)) {
            return $this->generateResponse(null, "failed", "At least one field (username, password, or email) must be provided to update.", 400);
        }

        if ($userRole === "user" && $id != $this->auth->getUserId()) {
            return $this->generateResponse(null, "failed", "Users can only update their own data.", 403);
        }

        try {
            $fields = [];
            $values = [];

            if (!empty($body->username)) {
                $fields[] = "username = ?";
                $values[] = $body->username;
            }
            if (!empty($body->password)) {
                $fields[] = "password = ?";
                $values[] = password_hash($body->password, PASSWORD_BCRYPT);
            }
            if (!empty($body->email)) {
                $fields[] = "email = ?";
                $values[] = $body->email;
            }

            if (!empty($body->role) && $userRole === "admin") {
                $fields[] = "role = ?";
                $values[] = $body->role;
            }

            if (empty($fields)) {
                return $this->generateResponse(null, "failed", "No valid fields provided to update.", 400);
            }

            $values[] = $id;

            $sql = "UPDATE user_tbl SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            return $this->generateResponse(null, "success", "User updated successfully.", 200);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }

    // Update product data (role-based permissions)
    public function updateProduct($id, $body, $userRole) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->productname) && empty($body->productprize) && empty($body->productowner)) {
            return $this->generateResponse(null, "failed", "At least one field (productname, productprize, or productowner) must be provided to update.", 400);
        }

        if ($userRole !== "seller" && $userRole !== "admin") {
            return $this->generateResponse(null, "failed", "Only sellers and admins can update products.", 403);
        }

        try {
            $fields = [];
            $values = [];

            if (!empty($body->productname)) {
                $fields[] = "productname = ?";
                $values[] = $body->productname;
            }
            if (!empty($body->productprize)) {
                $fields[] = "productprize = ?";
                $values[] = $body->productprize;
            }
            if (!empty($body->productowner)) {
                $fields[] = "productowner = ?";
                $values[] = $body->productowner;
            }

            if (empty($fields)) {
                return $this->generateResponse(null, "failed", "No valid fields provided to update.", 400);
            }

            // Ensure that a seller can only update their own products
            if ($userRole === "seller") {
                $sql = "SELECT productowner FROM product_tbl WHERE productid = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$id]);
                $product = $stmt->fetch();

                if (!$product || $product['productowner'] !== $this->auth->getUsername()) {
                    return $this->generateResponse(null, "failed", "Sellers can only update their own products.", 403);
                }
            }

            $values[] = $id;

            $sql = "UPDATE product_tbl SET " . implode(", ", $fields) . " WHERE productid = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            return $this->generateResponse(null, "success", "Product updated successfully.", 200);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }

    // Update cart data (role-based permissions)
    public function updateCart($id, $body, $userRole) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->username) && empty($body->productid) && empty($body->quantity)) {
            return $this->generateResponse(null, "failed", "At least one field (username, productid, or quantity) must be provided to update.", 400);
        }

        if ($userRole === "user" && $id != $this->auth->getUserId()) {
            return $this->generateResponse(null, "failed", "Users can only update their own cart.", 403);
        }

        try {
            $fields = [];
            $values = [];

            if (!empty($body->username)) {
                $fields[] = "username = ?";
                $values[] = $body->username;
            }
            if (!empty($body->productid)) {
                $fields[] = "productid = ?";
                $values[] = $body->productid;
            }
            if (!empty($body->quantity)) {
                $fields[] = "quantity = ?";
                $values[] = $body->quantity;
            }

            if (empty($fields)) {
                return $this->generateResponse(null, "failed", "No valid fields provided to update.", 400);
            }

            $values[] = $id;

            $sql = "UPDATE cart_tbl SET " . implode(", ", $fields) . " WHERE cartid = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($values);

            return $this->generateResponse(null, "success", "Cart updated successfully.", 200);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }
}

?>
