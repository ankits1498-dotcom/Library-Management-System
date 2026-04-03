CREATE DATABASE IF NOT EXISTS `library_management_system`;
USE `library_management_system`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'librarian') NOT NULL DEFAULT 'librarian',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `books` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `author` VARCHAR(120) NOT NULL,
    `isbn` VARCHAR(60) NOT NULL UNIQUE,
    `category_id` INT NOT NULL,
    `publisher` VARCHAR(120) NULL,
    `published_year` YEAR NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `available_quantity` INT NOT NULL DEFAULT 1,
    `shelf_location` VARCHAR(80) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_books_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS `members` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NULL,
    `phone` VARCHAR(30) NULL,
    `address` TEXT NULL,
    `membership_no` VARCHAR(60) NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

CREATE TABLE IF NOT EXISTS `issued_books` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `book_id` INT NOT NULL,
    `member_id` INT NOT NULL,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,
    `return_date` DATE NULL,
    `status` ENUM('issued', 'returned') NOT NULL DEFAULT 'issued',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_issued_book` FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_issued_member` FOREIGN KEY (`member_id`) REFERENCES `members`(`id`) ON DELETE CASCADE
);

INSERT INTO `categories` (`name`, `description`)
SELECT * FROM (
    SELECT 'Fiction', 'Novels, literature, and storytelling titles'
) AS tmp
WHERE NOT EXISTS (
    SELECT `name` FROM `categories` WHERE `name` = 'Fiction'
) LIMIT 1;
