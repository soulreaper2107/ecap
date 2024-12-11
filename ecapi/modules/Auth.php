<?php

class Authentication {

    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function isAuthorized() {
        // Compare request token to db token
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
       return $this->getToken() === $headers['authorization'];
    }

    private function getToken() {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);

        // Fetch the token for a user from the user_tbl or seller_tbl
        $sqlString = "SELECT token FROM user_tbl WHERE username=?";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$headers['x-auth-user']]);
            $result = $stmt->fetchAll()[0];
            return $result['token'] ?? '';  // Return token if exists, otherwise return empty string
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return "";
    }

    private function generateHeader() {
        $header = [
            "typ" => "JWT",
            "alg" => "HS256",
            "app" => "Ecommerce-ecAPI",
            "dev" => "Ryu, Robert"
        ];
        return base64_encode(json_encode($header));
    }

    private function generatePayload($id, $username) {
        $payload = [
            "uid" => $id,
            "uc" => $username,
            "email" => "uyrc2107@gmail.com", // You can adjust this email logic
            "date" => date_create(),
            "exp" => date("Y-m-d H-i-s")
        ];
        return base64_encode(json_encode($payload));
    }

    private function generateToken($id, $username) {
        $header = $this->generateHeader();
        $payload = $this->generatePayload($id, $username);
        $signature = hash_hmac("sha256", "$header.$payload", TOKEN_KEY);

        // Return only the signature encoded in base64
        return base64_encode($signature);
    }

    private function isSamePassword($inputPassword, $existingHash) {
        $hash = crypt($inputPassword, $existingHash);
        return $hash === $existingHash;
    }

    private function encryptPassword($password) {
        $hashFormat = "$2y$10$";
        $saltLength = 22;
        $salt = $this->generateSalt($saltLength);
        return crypt($password, $hashFormat . $salt);
    }

    public function saveToken($token, $username) {
        $errmsg = "";
        $code = 0;

        try {
            // Update the token in user_tbl (for regular users) or seller_tbl (for sellers)
            $sqlString = "UPDATE user_tbl SET token=? WHERE username = ?";
            $sql = $this->pdo->prepare($sqlString);
            $sql->execute([$token, $username]);

            $code = 200;
            $data = null;

            return array("data" => $data, "code" => $code);
        } catch (\PDOException $e) {
            $errmsg = $e->getMessage();
            $code = 400;
        }

        return array("errmsg" => $errmsg, "code" => $code);
    }

    private function generateSalt($length) {
        $urs = md5(uniqid(mt_rand(), true));
        $b64String = base64_encode($urs);
        $mb64String = str_replace("+", ".", $b64String);
        return substr($mb64String, 0, $length);
    }

    public function login($body): array {
        if (!$body || !isset($body['username']) || !isset($body['password'])) {
            return [
                "payload" => null,
                "remarks" => "failed",
                "message" => "Username and password are required.",
                "code" => 400
            ];
        }

        $username = $body['username'];
        $password = $body['password'];

        try {
            // Fetch user from user_tbl (for regular users) or seller_tbl (for sellers)
            $stmt = $this->pdo->prepare("SELECT * FROM user_tbl WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Generate a new token
                    $token = $this->generateToken($user['id'], $username);
                    $this->saveToken($token, $username);

                    return [
                        "payload" => ["token" => $token],
                        "remarks" => "success",
                        "message" => "Login successful.",
                        "code" => 200
                    ];
                } else {
                    return [
                        "payload" => null,
                        "remarks" => "failed",
                        "message" => "Invalid password.",
                        "code" => 401
                    ];
                }
            } else {
                return [
                    "payload" => null,
                    "remarks" => "failed",
                    "message" => "Username does not exist.",
                    "code" => 401
                ];
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                "payload" => null,
                "remarks" => "failed",
                "message" => "Internal server error.",
                "code" => 500
            ];
        }
    }

    public function addAccount($body) {
        $values = [];
        $errmsg = "";
        $code = 0;

        // Encrypt password
        $body['password'] = $this->encryptPassword($body['password']);

        // Gather values for SQL
        foreach ($body as $value) {
            array_push($values, $value);
        }

        try {
            // Insert into user_tbl or seller_tbl depending on the role
            $sqlString = "INSERT INTO user_tbl(username, password, email) VALUES (?,?,?)";  // Insert into user_tbl
            $sql = $this->pdo->prepare($sqlString);
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

    /**
     * Insert data into a specified table
     * 
     * @param string $tableName The name of the table to insert into
     * @param array $data Associative array where keys are column names and values are column values
     * @return array Response with code and message
     */
    public function postData($tableName, $data) {
        try {
            // Generate the SQL query for insertion
            $columns = implode(", ", array_keys($data)); // Get the column names
            $placeholders = implode(", ", array_fill(0, count($data), "?")); // Generate placeholders for prepared statements
            $sqlString = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
            
            // Prepare and execute the statement
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute(array_values($data));

            return [
                "payload" => null,
                "remarks" => "success",
                "message" => "Data inserted successfully.",
                "code" => 200
            ];
        } catch (\PDOException $e) {
            return [
                "payload" => null,
                "remarks" => "failed",
                "message" => "Error: " . $e->getMessage(),
                "code" => 400
            ];
        }
    }
}

?>
