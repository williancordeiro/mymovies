<?php

namespace App\Models;

use Core\Database\ActiveRecord\Model;
use Core\Database\Database;

/**
 * @property int $id
 * @property string $title
 * @property string $overview
 * @property string $poster_path
 * @property string $release_date
 * @property float $vote_average
 */
class Movie extends Model
{
    protected static string $table = 'movies';

    protected static array $columns = [
        'title',
        'overview',
        'poster_path',
        'release_date',
        'vote_average'
    ];

    protected ?int $id = null;
    protected string $title;
    protected ?string $overview = null;
    protected ?string $poster_path = null;
    protected ?string $release_date = null;
    protected ?float $vote_average = null;

    public static function saveFromTmdb(array $data): void
    {
        $pdo = Database::getDatabaseConn();
        $sql = "INSERT IGNORE INTO movies (id, title, overview, poster_path, release_date, vote_average)
                VALUES (:id, :title, :overview, :poster_path, :release_date, :vote_average)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'           => $data['id'],
            ':title'        => $data['title'] ?? '',
            ':overview'     => $data['overview'] ?? null,
            ':poster_path'  => $data['poster_path'] ?? null,
            ':release_date' => $data['release_date'] ?? null,
            ':vote_average' => $data['vote_average'] ?? null,
        ]);
    }
}
