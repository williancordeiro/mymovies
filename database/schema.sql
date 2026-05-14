SET foreign_key_checks = 0;

DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) NOT NULL,
    `handle` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(64) NOT NULL UNIQUE,
    `encrypted_password` VARCHAR(255) NOT NULL,
    `role` ENUM('Default', 'Admin') NOT NULL DEFAULT 'Default',
    `avatar_file` VARCHAR(255) DEFAULT 'avatar.png',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

SET foreign_key_checks = 1;