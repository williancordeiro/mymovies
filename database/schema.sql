SET foreign_key_checks = 0;

DROP TABLE IF EXISTS users;

CREATE OR REPLACE TABLE `users` (
	`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(64) NOT NULL,
	`email` VARCHAR(64) NOT NULL UNIQUE,
	`password` VARCHAR(255) NOT NULL,
	`avatar` VARCHAR(255),
	`admin` DECIMAL(1) NOT NULL,
	`created_at` TIMESTAMP NOT NULL,
	`updated_at` TIMESTAMP,
	PRIMARY KEY(`id`)
); ENGINE=InnoDB;

SET foreign_key_checks = 1;