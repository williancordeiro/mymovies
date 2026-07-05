<?php

namespace Database\Populate;

use Core\Database\Database;
use App\Models\Tag;

class TagsPopulate {

    public static function populate(): void
    {
        $tags = [
            ['description' => 'Boa atuação'],
            ['description' => 'Roteiro fraco'],
            ['description' => 'Ótima fotografia'],
            ['description' => 'Final ruim'],
            ['description' => 'História confusa'],
            ['description' => 'Atuação ruim'],
            ['description' => 'Personagens cativantes'],
            ['description' => 'Ritmo lento'],
            ['description' => 'Diálogos inteligentes'],
            ['description' => 'Edição mal feita'],
        ];
    
        foreach ($tags as $data) {
            $tag = new Tag($data);
            if (!$tag->save()) {
                print_r($tag->errors());
                die("Erro ao salvar tag description={$data['description']}");
            }
        }

        echo "Tags populadas com sucesso!.\n";
    }
}