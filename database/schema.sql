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

DROP TABLE IF EXISTS movie_ratings;

CREATE TABLE movie_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY user_movie (user_id, movie_id)
);

DROP TABLE IF EXISTS custom_movies;

CREATE TABLE custom_movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    release_year INT,
    poster_url VARCHAR(255),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    status ENUM('Vou assistir', 'Assistido', 'Não terminei'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

SET foreign_key_checks = 1;