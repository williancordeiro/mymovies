SET foreign_key_checks = 0;

DROP TABLE IF EXISTS movies;

CREATE TABLE `movies` (
    `id` INTEGER UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `overview` TEXT,
    `poster_path` VARCHAR(255),
    `release_date` VARCHAR(20),
    `vote_average` DECIMAL(3,1),
    PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS users;

CREATE TABLE `users` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(64) NOT NULL,
    `handle` VARCHAR(64) NOT NULL UNIQUE,
    `email` VARCHAR(64) NOT NULL UNIQUE,
    `encrypted_password` VARCHAR(255) NOT NULL,
    `role` ENUM('Default', 'Admin') NOT NULL DEFAULT 'Default',
    `avatar_file` VARCHAR(255) DEFAULT 'avatar.png',
    `banner_file` VARCHAR(255) DEFAULT 'banner.png',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS movies_rating;

CREATE TABLE `movies_rating` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INTEGER UNSIGNED NOT NULL,
    `movie_id` INTEGER UNSIGNED NOT NULL,
    `rating` TINYINT NOT NULL CHECK (
        rating >= 1
        and rating <= 5
    ),
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_movies_rating_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movies_rating_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
);


DROP TABLE IF EXISTS rating_tags;

CREATE TABLE `rating_tags` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,

    PRIMARY KEY (`id`)
);

DROP TABLE IF EXISTS movie_rating_tags;

CREATE TABLE `movie_rating_tags` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `movie_rating_id` INTEGER UNSIGNED NOT NULL,
    `rating_tag_id` INTEGER UNSIGNED NOT NULL,

    PRIMARY KEY (`id`),

    UNIQUE KEY `rating_tag` (`movie_rating_id`, `rating_tag_id`),
    CONSTRAINT `fk_rating`
        FOREIGN KEY (`movie_rating_id`) REFERENCES `movie_rating` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tag`
        FOREIGN KEY (`rating_tag_id`) REFERENCES `rating_tags` (`id`) ON DELETE CASCADE
);

DROP TABLE IF EXISTS user_images;

CREATE TABLE `user_images` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INTEGER UNSIGNED NOT NULL,
    `image_file` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_user_images_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

SET foreign_key_checks = 1;