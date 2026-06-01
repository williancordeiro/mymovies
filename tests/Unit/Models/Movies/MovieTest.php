<?php

namespace Tests\Unit\Models\Movies;

use PHPUnit\Framework\TestCase;
use App\Models\Movie;

class MovieTest extends TestCase
{
    public function test_should_initialize_movie_with_correct_data_in_memory(): void
    {
        $movieData = [
            'title'        => 'Interstellar',
            'overview'     => 'Uma viagem fantástica pelo espaço-tempo.',
            'poster_path'  => '/interstellar.jpg',
            'release_date' => '2014-11-06',
            'vote_average' => 8.6
        ];

        $movie = new Movie($movieData);

        $this->assertEquals('Interstellar', $movie->title);
        $this->assertEquals('Uma viagem fantástica pelo espaço-tempo.', $movie->overview);
        $this->assertEquals('/interstellar.jpg', $movie->poster_path);
        $this->assertEquals('2014-11-06', $movie->release_date);
        $this->assertEquals(8.6, $movie->vote_average);
    }

    public function test_should_allow_modifying_attributes_before_saving(): void
    {
        $movie = new Movie(['title' => 'Avatar Antigo']);

        $movie->title = 'Avatar O Caminho da Água';

        $this->assertEquals('Avatar O Caminho da Água', $movie->title);
    }

    public function test_should_start_with_empty_errors_array(): void
    {
        $movie = new Movie();

        $this->assertIsArray($movie->errors());
        $this->assertEmpty($movie->errors());
    }
}
