<?php
include_once "Common.php";

class Get extends Common {
    protected $pdo;
    protected $auth;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $this->auth = new Authentication($pdo); // Initialize Authentication instance
    }

    // Get users (only accessible by admin)
    public function getUsers($userRole) {
        if ($userRole !== "admin") {
            return $this->generateResponse(null, "failed", "Admins only can view users.", 403);
        }

        $result = $this->getDataByTable('user_tbl', "1=1", $this->pdo);

        if ($result['code'] === 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved users.", $result['code']);
        }

        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Get products (only accessible by admin or seller for their own products)
    public function getProducts(PDO $pdo) {
        // Retrieve userRole and username (Assuming these are coming from authentication or session)
        $userRole = $this->auth->getRole(); // Get role from authentication
        $username = $this->auth->getUsername(); // Get username from authentication

        if ($userRole === "user") {
            return $this->generateResponse(null, "failed", "Users cannot retrieve product data.", 403);
        }

        if ($userRole === "seller") {
            // Fetch only products owned by the seller
            $sqlString = "SELECT 
                            p.productid, 
                            p.productname, 
                            p.productprize, 
                            p.productowner
                          FROM 
                            product_tbl p
                          WHERE 
                            p.productowner = :username";
            $stmt = $pdo->prepare($sqlString);
            $stmt->execute([':username' => $username]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Admin can retrieve all products
            $sqlString = "SELECT 
                            p.productid, 
                            p.productname, 
                            p.productprize, 
                            s.username AS productowner
                          FROM 
                            product_tbl p
                          JOIN 
                            user_tbl s ON p.productowner = s.username";
            $result = $this->getDataBySQL($sqlString, $pdo);
        }

        if ($result['code'] === 200) {
            return $this->generateResponse($result['data'], "success", "Successfully retrieved products.", $result['code']);
        }

        return $this->generateResponse(null, "failed", $result['errmsg'], $result['code']);
    }

    // Get cart for a specific user
    public function getCart($username, $userRole) {
        if ($userRole === "user" && $username != $this->auth->getUsername()) {
            return $this->generateResponse(null, "failed", "Users can only view their own cart.", 403);
        }

        $sqlString = "SELECT 
                        c.cartid, 
                        c.username, 
                        c.productid, 
                        p.productname, 
                        p.productprize, 
                        c.sellername, 
                        c.quantity
                    FROM 
                        cart_tbl c
                    JOIN 
                        product_tbl p ON c.productid = p.productid
                    WHERE 
                        c.username = :username";

        $stmt = $this->pdo->prepare($sqlString);
        $stmt->execute([':username' => $username]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($result) {
            return $this->generateResponse($result, "success", "Successfully retrieved cart for user.", 200);
        }

        return $this->generateResponse(null, "failed", "No records found.", 404);
    }

    // Get product details by product ID (only accessible by admin or seller for their own products)
    public function getProductById($productId, $userRole, $username) {
        if ($userRole === "user") {
            return $this->generateResponse(null, "failed", "Users cannot view product details.", 403);
        }

        // If seller, they can view only their own product
        if ($userRole === "seller") {
            $sqlString = "SELECT 
                            p.productid, 
                            p.productname, 
                            p.productprize, 
                            p.productowner
                          FROM 
                            product_tbl p
                          WHERE 
                            p.productid = :productId
                            AND p.productowner = :username";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([':productId' => $productId, ':username' => $username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Admin can view any product
            $sqlString = "SELECT 
                            p.productid, 
                            p.productname, 
                            p.productprize, 
                            s.username AS productowner
                          FROM 
                            product_tbl p
                          JOIN 
                            user_tbl s ON p.productowner = s.username
                          WHERE 
                            p.productid = :productId";
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([':productId' => $productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($result) {
            return $this->generateResponse($result, "success", "Successfully retrieved product details.", 200);
        }

        return $this->generateResponse(null, "failed", "No product found.", 404);
    }
}
?>
