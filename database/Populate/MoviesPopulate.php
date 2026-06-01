<?php

namespace Database\Populate;

use Core\Database\Database;

class MoviesPopulate {

    public static function populate(): void {
        $db = Database::getDatabaseConn();
        
        $sql = "INSERT INTO movies (id, title, overview, poster_path, release_date, vote_average) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            1, 
            'Filme de Teste', 
            'Uma sinopse de teste para o filme populado.', 
            '/test.jpg', 
            '2024-01-01', 
            8.5
        ]);

        echo "Tabela de filmes populada com sucesso.\n";
    }
}
