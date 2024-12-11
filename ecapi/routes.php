<?php

require_once "C:/xampp/htdocs/ecapi/config/database.php";
require_once "C:/xampp/htdocs/ecapi/modules/Get.php";
require_once "C:/xampp/htdocs/ecapi/modules/Post.php";
require_once "C:/xampp/htdocs/ecapi/modules/patch.php";
require_once "C:/xampp/htdocs/ecapi/modules/archive.php";
require_once "C:/xampp/htdocs/ecapi/modules/Auth.php";
require_once "C:/xampp/htdocs/ecapi/modules/Common.php";

$db = new Connection();
$pdo = $db->connect();

$post = new Post($pdo);
$get = new Get($pdo);
$patch = new Patch($pdo);
$archive = new Archive($pdo);
$auth = new Authentication($pdo);

if (isset($_REQUEST['request'])) {
    $request = explode("/", $_REQUEST['request']);
} else {
    echo "URL does not exist.";
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {

    case "GET":
        if ($auth->isAuthorized()) {
            $role = $auth->getUserRole(); // Retrieve the role of the current user
            if ($role == 'user') {
                switch ($request[0]) {
                    case "cart":
                        echo $get->prettyPrint($get->getCart($auth->getUsername()));
                        break;
                    case "product":
                        if (isset($request[1])) {
                            echo $get->prettyPrint($get->getProductById($request[1]));
                        } else {
                            http_response_code(400);
                            echo $get->prettyPrint(["error" => "Product ID is required"]);
                        }
                        break;
                    default:
                        http_response_code(404);
                        echo $get->prettyPrint(["error" => "Invalid endpoint"]);
                }
            } elseif ($role == 'seller' || $role == 'admin') {
                switch ($request[0]) {
                    case "users":
                        if ($role == 'admin') {
                            echo $get->prettyPrint($get->getUsers());
                        } else {
                            echo $get->prettyPrint(["error" => "Unauthorized"]);
                        }
                        break;
                    case "products":
                        echo $get->prettyPrint($get->getProducts());
                        break;
                    case "cart":
                        echo $get->prettyPrint($get->getCart($auth->getUsername()));
                        break;
                    default:
                        http_response_code(404);
                        echo $get->prettyPrint(["error" => "Invalid endpoint"]);
                }
            }
        } else {
            echo $get->prettyPrint(["error" => "Unauthorized"]);
        }
        break;

    case "POST":
        $body = json_decode(file_get_contents("php://input"), true);
        if ($request[0] === "login" || $request[0] === "signup") {
            if ($request[0] === "login") {
                echo $get->prettyPrint($auth->login($body));
            } elseif ($request[0] === "signup") {
                echo $get->prettyPrint($auth->addAccount($body));
            }
        } elseif ($auth->isAuthorized()) {
            $role = $auth->getUserRole();
            switch ($request[0]) {
                case "user":
                    echo $get->prettyPrint($post->postUser($body));
                    break;

                case "product":
                    if ($role == 'seller') {
                        echo $get->prettyPrint($post->postProduct($body));
                    } else {
                        http_response_code(403);
                        echo $get->prettyPrint(["error" => "Only sellers can post products."]);
                    }
                    break;

                case "cart":
                    echo $get->prettyPrint($post->postCart($body));
                    break;

                default:
                    http_response_code(404);
                    echo $get->prettyPrint(["error" => "Invalid endpoint"]);
                    break;
            }
        } else {
            echo $get->prettyPrint(["error" => "Unauthorized"]);
        }
        break;

    case "DELETE":
        if ($auth->isAuthorized()) {
            $role = $auth->getUserRole();
            switch ($request[0]) {
                case "user":
                    if ($role == 'admin' || $auth->getUsername() == $request[1]) {
                        echo $get->prettyPrint($archive->deleteUser($request[1]));
                    } else {
                        http_response_code(403);
                        echo $get->prettyPrint(["error" => "You can only delete your own user data"]);
                    }
                    break;

                case "product":
                    if ($role == 'seller' || $role == 'admin') {
                        echo $get->prettyPrint($archive->deleteProduct($request[1], $auth->getUsername()));
                    } else {
                        http_response_code(403);
                        echo $get->prettyPrint(["error" => "Only sellers can delete their own products"]);
                    }
                    break;

                case "cart":
                    echo $get->prettyPrint($archive->deleteCartItem($request[1], $auth->getUsername()));
                    break;

                default:
                    http_response_code(404);
                    echo $get->prettyPrint(["error" => "Invalid endpoint"]);
                    break;
            }
        } else {
            echo $get->prettyPrint(["error" => "Unauthorized"]);
        }
        break;

    case "PATCH":
        $body = json_decode(file_get_contents("php://input"));
        if ($auth->isAuthorized()) {
            $role = $auth->getUserRole();
            switch ($request[0]) {
                case "user":
                    if ($auth->getUsername() == $request[1] || $role == 'admin') {
                        echo $get->prettyPrint($patch->updateUser($request[1], $body));
                    } else {
                        http_response_code(403);
                        echo $get->prettyPrint(["error" => "You can only update your own user data"]);
                    }
                    break;

                case "product":
                    if ($role == 'seller' || $role == 'admin') {
                        echo $get->prettyPrint($patch->updateProduct($request[1], $body, $auth->getUsername()));
                    } else {
                        http_response_code(403);
                        echo $get->prettyPrint(["error" => "Only sellers can update their own products"]);
                    }
                    break;

                case "cart":
                    echo $get->prettyPrint($patch->updateCart($request[1], $body, $auth->getUsername()));
                    break;

                default:
                    http_response_code(404);
                    echo $get->prettyPrint(["error" => "Invalid endpoint"]);
                    break;
            }
        } else {
            echo $get->prettyPrint(["error" => "Unauthorized"]);
        }
        break;

    default:
        http_response_code(400);
        echo $get->prettyPrint(["error" => "Invalid Request Method"]);
        break;
}

$pdo = null;
?>