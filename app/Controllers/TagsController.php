<?php

namespace App\Controllers;

use Core\Http\Controllers\Controller;
use App\Models\Tag;
use Core\Http\Request;
use Lib\FlashMessage;
use Core\Constants\Constants;

class TagsController extends Controller
{
    public function listAll(Request $request): void
    {
        $page = (int) ($request->getParam('page') ?? 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $tags = Tag::all();
        $total = count($tags);
        $tags = array_slice($tags, $offset, $perPage);

        $this->json([
            'tags' => array_map(fn($tag) => [
                'id'            => $tag->id,
                'description'   => $tag->description
            ], $tags),
            'total' => $total,
            'page'  => $page,
        ]);
    }
}