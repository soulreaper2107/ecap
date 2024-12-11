<?php

include_once "Common.php";
include_once "Auth.php";

class Post extends Common {
    protected $pdo;
    protected $auth;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->auth = new Authentication($pdo);
    }

    public function postUser($body, $headers) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->username) || empty($body->password) || empty($body->email) || empty($body->role)) {
            return $this->generateResponse(null, "failed", "Username, password, email, and role are required.", 400);
        }

        if ($body->role === "admin") {
            if (!isset($headers['Authorization']) || $headers['Authorization'] !== 'VD2cIwtRetq1w3Gvk/qmM6u24OeiybIsBYTb6n/Qgh8=') {
                return $this->generateResponse(null, "failed", "Invalid authorization key for admin.", 403);
            }

            if (!isset($body->key) || $body->key !== 'z+nZM6QrF3FvadO85veZH5aUXVextBzRVzA8/31l4wU=') {
                return $this->generateResponse(null, "failed", "Invalid admin creation key.", 403);
            }
        }

        try {
            $hashedPassword = password_hash($body->password, PASSWORD_BCRYPT);
            $result = $this->postData("user_tbl", [
                "username" => $body->username,
                "password" => $hashedPassword,
                "email" => $body->email,
                "role" => $body->role
            ], $this->pdo);

            if ($result['code'] == 200) {
                $this->logger(parent::getLoggedInUsername(), "POST", "New user registered: {$body->username}.");
                return $this->generateResponse($result['data'], "success", "User registered successfully.", 201);
            }

            return $this->generateResponse(null, "failed", $result['errmsg'], 400);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }

    public function postProduct($body, $userRole) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->productname) || empty($body->productprize) || empty($body->productowner)) {
            return $this->generateResponse(null, "failed", "Product name, price, and owner are required.", 400);
        }

        if ($userRole !== "seller" && $userRole !== "admin") {
            return $this->generateResponse(null, "failed", "Only sellers or admins can post products.", 403);
        }

        try {
            $result = $this->postData("product_tbl", [
                "productname" => $body->productname,
                "productprize" => $body->productprize,
                "productowner" => $body->productowner
            ], $this->pdo);

            if ($result['code'] == 200) {
                $this->logger(parent::getLoggedInUsername(), "POST", "New product added: {$body->productname}.");
                return $this->generateResponse($result['data'], "success", "Product added successfully.", 201);
            }

            return $this->generateResponse(null, "failed", $result['errmsg'], 400);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }

    public function postCart($body, $userRole) {
        if (is_array($body)) {
            $body = (object) $body;
        }

        if (empty($body->username) || empty($body->productid) || empty($body->quantity)) {
            return $this->generateResponse(null, "failed", "Username, product ID, and quantity are required.", 400);
        }

        if ($userRole === "user") {
            return $this->generateResponse(null, "failed", "Users are not allowed to post to cart.", 403);
        }

        try {
            $productQuery = "SELECT productname, productprize, productowner FROM product_tbl WHERE productid = :productid";
            $stmt = $this->pdo->prepare($productQuery);
            $stmt->execute([':productid' => $body->productid]);
            $product = $stmt->fetch();

            if (!$product) {
                return $this->generateResponse(null, "failed", "Product not found.", 404);
            }

            $result = $this->postData("cart_tbl", [
                "username" => $body->username,
                "productid" => $body->productid,
                "productname" => $product['productname'],
                "productprize" => $product['productprize'],
                "sellername" => $product['productowner'],
                "quantity" => $body->quantity
            ], $this->pdo);

            if ($result['code'] == 200) {
                $this->logger(parent::getLoggedInUsername(), "POST", "Added product to cart for user: {$body->username}.");
                return $this->generateResponse($result['data'], "success", "Product added to cart.", 201);
            }

            return $this->generateResponse(null, "failed", $result['errmsg'], 400);
        } catch (\Exception $e) {
            return $this->generateResponse(null, "failed", $e->getMessage(), 500);
        }
    }
}

?>
