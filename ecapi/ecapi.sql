CREATE DATABASE ecapi;

USE ecapi;

CREATE TABLE user_tbl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'seller', 'user') DEFAULT 'user',
    token VARCHAR(255) NULL
);

CREATE TABLE product_tbl (
    productid INT AUTO_INCREMENT PRIMARY KEY,
    productname VARCHAR(255) NOT NULL,
    productprize DECIMAL(10, 2) NOT NULL,
    productowner VARCHAR(255) NOT NULL,
    FOREIGN KEY (productowner) REFERENCES user_tbl(username) ON DELETE CASCADE
);


CREATE TABLE cart_tbl (
    cartid INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    productid INT NOT NULL,
    productname VARCHAR(255) NOT NULL,
    productprize DECIMAL(10, 2) NOT NULL,
    sellername VARCHAR(255) NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    FOREIGN KEY (username) REFERENCES user_tbl(username) ON DELETE CASCADE,
    FOREIGN KEY (productid) REFERENCES product_tbl(productid) ON DELETE CASCADE,
    FOREIGN KEY (sellername) REFERENCES user_tbl(username) ON DELETE CASCADE
);

DELIMITER //

CREATE TRIGGER enforce_productowner_role
BEFORE INSERT ON product_tbl
FOR EACH ROW
BEGIN
    DECLARE user_role VARCHAR(50);
    SELECT role INTO user_role FROM user_tbl WHERE username = NEW.productowner;

    IF user_role != 'seller' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only users with the role seller can be product owners.';
    END IF;
END;
//

DELIMITER ;

DELIMITER //

CREATE TRIGGER enforce_sellername_role
BEFORE INSERT ON cart_tbl
FOR EACH ROW
BEGIN
    DECLARE user_role VARCHAR(50);
    SELECT role INTO user_role FROM user_tbl WHERE username = NEW.sellername;

    IF user_role != 'seller' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only users with the role seller can be seller names.';
    END IF;
END;
//

DELIMITER ;


