<?php
require_once "C:/xampp/htdocs/ecAPI/config/database.php";
try {
    $db = new Connection();
    $pdo = $db->connect();
    echo "Database connection successful!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
