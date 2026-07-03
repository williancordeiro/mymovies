<?php

namespace Database\Populate;

use App\Models\RatingTag;

class RatingTagsPopulate
{
    public static function populate(): void
    {
        $tags = [
            'Boa atuação',
            'Roteiro fraco',
            'Ótima fotografia',
            'Final ruim',
            'Trilha sonora incrível',
            'Efeitos especiais impressionantes',
        ];

        foreach ($tags as $tag) {
            $ratingTag = new RatingTag([
                'tag' => $tag,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $ratingTag->save();
        }

        echo "Tags populadas com sucesso.\n";
    }
}