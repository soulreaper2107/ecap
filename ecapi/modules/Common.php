<?php
class Common {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function prettyPrint($data) {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function getLogFileName($date = null) {
        $date = $date ?? date("Y-m-d");
        return $date . ".log";
    }

    protected function getLoggedInUsername() {
        // Ensure the token is available from the request headers
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        if (!isset($headers['authorization'])) {
            return 'Guest';
        }

        $token = $headers['authorization'];
        $query = "SELECT name FROM credentials WHERE token = ?";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$token]);
            $result = $stmt->fetch();
            return $result['name'] ?? 'Guest';
        } catch (\PDOException $e) {
            error_log($e->getMessage());
        }

        return 'Guest';
    }

    protected function logger($user, $method, $action) {
        $filename = date("Y-m-d") . ".log";
        $datetime = date("Y-m-d H:i:s");
        $logMessage = "$datetime,$method,$user,$action" . PHP_EOL;
        error_log($logMessage, 3, "C:/xampp/htdocs/ecAPI/logs/$filename");
    }

    private function generateInsertString($tablename, $body) {
        $keys = array_keys($body);
        $fields = implode(",", $keys);
        $parameter_array = [];
        for ($i = 0; $i < count($keys); $i++) {
            $parameter_array[$i] = "?";
        }
        $parameters = implode(',', $parameter_array);
        $sql = "INSERT INTO $tablename($fields) VALUES ($parameters)";
        return $sql;
    }

    // Fetch data from a specified table based on conditions
    protected function getDataByTable($tableName, $condition, \PDO $pdo) {
        $sqlString = "SELECT id, username FROM $tableName WHERE $condition";
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                foreach ($result as $record) {
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code" => $code, "data" => $data);
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return array("code" => $code, "errmsg" => $errmsg);
    }

    // Fetch data by a custom SQL query
    protected function getDataBySQL($sqlString, \PDO $pdo) {
        $data = [];
        $errmsg = "";
        $code = 0;

        try {
            if ($result = $pdo->query($sqlString)->fetchAll()) {
                foreach ($result as $record) {
                    array_push($data, $record);
                }
                $result = null;
                $code = 200;
                return array("code" => $code, "data" => $data);
            } else {
                $errmsg = "No data found";
                $code = 404;
            }
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 403;
        }

        return array("code" => $code, "errmsg" => $errmsg);
    }

    // Function to generate a standard response format
    public function generateResponse($data, $remark, $message, $statusCode) {
        $status = array(
            "remark" => $remark,
            "message" => $message
        );

        http_response_code($statusCode);

        return array(
            "payload" => $data,
            "status" => $status,
            "prepared_by" => "Ryu",
            "date_generated" => date_create()
        );
    }

    // Function to insert data into a specific table
    public function postData($tableName, $body, \PDO $pdo) {
        $values = [];
        $errmsg = "";
        $code = 0;

        foreach ($body as $value) {
            array_push($values, $value);
        }

        try {
            $sqlString = $this->generateInsertString($tableName, $body);
            $sql = $pdo->prepare($sqlString);
            $sql->execute($values);

            $code = 200;
            $data = null;

            return array("data" => $data, "code" => $code);
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return array("errmsg" => $errmsg, "code" => $code);
    }

    // Renamed Methods to match required function calls
    public function updateCart($cartId, $updates, \PDO $pdo) {
        return $this->updateData("cart_tbl", $updates, "cartid = $cartId", $pdo);
    }

    public function getUsername($token) {
        return $this->getDataBySQL("SELECT username FROM credentials WHERE token = '$token'", $this->pdo);
    }

    public function updateProduct($productId, $updates, \PDO $pdo) {
        return $this->updateData("product_tbl", $updates, "productid = $productId", $pdo);
    }

    public function updateUser($username, $updates, \PDO $pdo) {
        return $this->updateData("user_tbl", $updates, "username = '$username'", $pdo);
    }

    public function getUserRole($username) {
        return $this->getDataBySQL("SELECT role FROM user_tbl WHERE username = '$username'", $this->pdo);
    }

    public function deleteProduct($productId, \PDO $pdo) {
        return $this->deleteData("product_tbl", "productid = $productId", $pdo);
    }

    public function deleteUser($username, \PDO $pdo) {
        return $this->deleteData("user_tbl", "username = '$username'", $pdo);
    }

    public function postCart($body, \PDO $pdo) {
        return $this->postData("cart_tbl", $body, $pdo);
    }

    public function postProduct($body, \PDO $pdo) {
        return $this->postData("product_tbl", $body, $pdo);
    }

    public function postUser($body, \PDO $pdo) {
        return $this->postData("user_tbl", $body, $pdo);
    }

    public function getCart($username, \PDO $pdo) {
        return $this->getDataByTable("cart_tbl", "username = '$username'", $pdo);
    }

    public function getProducts(\PDO $pdo) {
        return $this->getDataByTable("product_tbl", "1=1", $pdo);
    }

    public function getProductById($productId, \PDO $pdo) {
        return $this->getDataBySQL("SELECT * FROM product_tbl WHERE productid = $productId", $pdo);
    }

    public function getUserId($username, \PDO $pdo) {
        return $this->getDataBySQL("SELECT id FROM user_tbl WHERE username = '$username'", $pdo);
    }

    // Helper Methods for Updates and Deletes
    private function updateData($tableName, $updates, $condition, \PDO $pdo) {
        $setString = "";
        $values = [];
        foreach ($updates as $key => $value) {
            $setString .= "$key = ?, ";
            array_push($values, $value);
        }
        $setString = rtrim($setString, ", ");
        $sqlString = "UPDATE $tableName SET $setString WHERE $condition";

        try {
            $stmt = $pdo->prepare($sqlString);
            $stmt->execute($values);

            if ($stmt->rowCount() > 0) {
                return $this->generateResponse(null, "success", "$tableName updated successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "No matching records found to update.", 404);
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error updating $tableName: " . $e->getMessage(), 500);
        }
    }

    private function deleteData($tableName, $condition, \PDO $pdo) {
        $sqlString = "DELETE FROM $tableName WHERE $condition";

        try {
            $stmt = $pdo->prepare($sqlString);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return $this->generateResponse(null, "success", "Record deleted successfully.", 200);
            } else {
                return $this->generateResponse(null, "failed", "No matching records found to delete.", 404);
            }
        } catch (\PDOException $e) {
            return $this->generateResponse(null, "failed", "Error deleting record: " . $e->getMessage(), 500);
        }
    }
}
?>
