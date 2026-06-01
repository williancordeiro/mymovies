<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ProfileImages;
use Core\Database\ActiveRecord\Model;

class ProfileImagesTest extends TestCase
{
    private Model $mockModel;
    private string $dummyImagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockModel = new class extends Model {
            protected static string $table = 'users';
            /**
             * @var array<string, string>
             */
            public array $errorsArray = [];
            public ?string $avatar_file = null;
            public ?string $banner_file = null;

            public function addError(string $field, string $message): void
            {
                $this->errorsArray[$field] = $message;
            }

            public function errors(?string $index = null): array|string|null
            {
                if ($index) {
                    return $this->errorsArray[$index] ?? null;
                }
                return $this->errorsArray;
            }
        };

        // Caminho de uma imagem real do próprio projeto para o getimagesize conseguir ler as dimensões
        $this->dummyImagePath = \Core\Constants\Constants::rootPath()->join('public/assets/images/defaults/banner.png');
    }

    public function test_should_reject_invalid_avatar_image_extensions(): void
    {
        $validations = ['extensions' => ['jpg', 'jpeg', 'png']];
        $service = new ProfileImages($this->mockModel, $validations, 'avatar_file');

        $fakeImage = [
            'name' => 'documento_perigoso.pdf',
            'tmp_name' => '/tmp/phpXYZ',
            'size' => 500,
            'error' => 0
        ];

        $result = $service->update($fakeImage);

        $this->assertFalse($result);
        $this->assertStringContainsString(
            'A Imagem deve ser um arquivo do tipo: jpg, jpeg, png',
            $this->mockModel->errors('avatar_file')
        );
    }

    public function test_should_reject_invalid_banner_image_extensions(): void
    {
        $validations = ['extensions' => ['jpg', 'jpeg', 'png']];
        $service = new ProfileImages($this->mockModel, $validations, 'banner_file');

        $fakeImage = [
            'name' => 'documento_perigoso.pdf',
            'tmp_name' => '/tmp/phpXYZ',
            'size' => 500,
            'error' => 0
        ];

        $result = $service->update($fakeImage);

        $this->assertFalse($result);
        $this->assertStringContainsString(
            'A Imagem deve ser um arquivo do tipo: jpg, jpeg, png',
            $this->mockModel->errors('banner_file')
        );
    }

    public function test_should_reject_avatar_images_exceeding_max_size(): void
    {
        $validations = ['max_size' => 2 * 1024 * 1024];
        $service = new ProfileImages($this->mockModel, $validations, 'avatar_file');

        $fakeImage = [
            'name' => 'foto_pesada.png',
            'tmp_name' => '/tmp/phpABC',
            'size' => 3 * 1024 * 1024,
            'error' => 0
        ];

        $result = $service->update($fakeImage);

        $this->assertFalse($result);
        $this->assertStringContainsString('A imagem excede o tamanho máximo permitido', $this->mockModel->errors('avatar_file'));
    }

    public function test_should_reject_banner_images_exceeding_max_size(): void
    {
        $validations = ['max_size' => 5 * 1024 * 1024];
        $service = new ProfileImages($this->mockModel, $validations, 'banner_file');

        $fakeImage = [
            'name' => 'foto_pesada.png',
            'tmp_name' => '/tmp/phpABC',
            'size' => 6 * 1024 * 1024,
            'error' => 0
        ];

        $result = $service->update($fakeImage);

        $this->assertFalse($result);
        $this->assertStringContainsString('A imagem excede o tamanho máximo permitido', $this->mockModel->errors('banner_file'));
    }

    public function test_should_accept_image_with_valid_aspect_ratio(): void
    {

        $validations = [
            'aspect_ratio' => ['min' => 0.1, 'max' => 10.0]
        ];
        $service = new ProfileImages($this->mockModel, $validations, 'avatar_file');

        $fakeImage = [
            'name' => 'avatar_correto.png',
            'tmp_name' => $this->dummyImagePath,
            'size' => 1000,
            'error' => 0
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to move uploaded file');

        $service->update($fakeImage);
    }

    public function test_should_reject_image_with_invalid_aspect_ratio(): void
    {
        $validations = [
            'aspect_ratio' => ['min' => 20.0, 'max' => 30.0]
        ];
        $service = new ProfileImages($this->mockModel, $validations, 'avatar_file');

        $fakeImage = [
            'name' => 'avatar_proporcao_errada.png',
            'tmp_name' => $this->dummyImagePath,
            'size' => 1000,
            'error' => 0
        ];

        $result = $service->update($fakeImage);

        $this->assertFalse($result);
        $this->assertNotNull($this->mockModel->errors('avatar_file'));
        $this->assertStringContainsString('A proporção da imagem deve estar entre', $this->mockModel->errors('avatar_file'));
    }

    public function test_should_return_default_path_when_no_image_is_set(): void
    {
        $service = new ProfileImages($this->mockModel, [], 'avatar_file');
        $this->assertEquals('/assets/images/defaults/avatar.png', $service->path());
    }
}
