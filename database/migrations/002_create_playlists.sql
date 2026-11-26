CREATE TABLE `playlists` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INTEGER UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_playlists_user_created_id` (`user_id`, `created_at`, `id`),
    CONSTRAINT `fk_playlists_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
);

CREATE TABLE `playlist_movies` (
    `id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `playlist_id` INTEGER UNSIGNED NOT NULL,
    `movie_id` INTEGER UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_playlist_movies_playlist_movie` (`playlist_id`, `movie_id`),
    KEY `idx_playlist_movies_playlist_created_id` (`playlist_id`, `created_at`, `id`),
    KEY `idx_playlist_movies_movie` (`movie_id`),
    CONSTRAINT `fk_playlist_movies_playlist` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_playlist_movies_movie` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE
);
