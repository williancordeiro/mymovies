CREATE TABLE `movies_favorites` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INTEGER UNSIGNED NOT NULL,
    `movie_id` INTEGER UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_movies_favorites_user_movie` (`user_id`, `movie_id`),
    KEY `idx_movies_favorites_user_created_id` (`user_id`, `created_at`, `id`),
    KEY `idx_movies_favorites_movie` (`movie_id`),
    CONSTRAINT `fk_movies_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_movies_favorites_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
);
