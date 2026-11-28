<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ProfileImages;
use Core\Database\ActiveRecord\Model;

class ProfileImagesTest extends TestCase
{
    private Model $mockModel;
    private string $dummyImagePath;
    /** @var array<int, string> */
    private array $temporaryImages = [];

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
            public bool $shouldUpdate = true;
            /** @var array<string, mixed> */
            public array $updatedData = [];

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

            public function update(array $data): bool
            {
                $this->updatedData = $data;
                if ($this->shouldUpdate) {
                    foreach ($data as $column => $value) {
                        $this->{$column} = $value;
                    }
                }

                return $this->shouldUpdate;
            }
        };

        // Caminho de uma imagem real do próprio projeto para o getimagesize conseguir ler as dimensões
        $this->dummyImagePath = \Core\Constants\Constants::rootPath()->join('public/assets/images/defaults/banner.png');
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryImages as $path) {
            @unlink($path);
        }

        parent::tearDown();
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

    public function test_should_accept_small_rectangular_image_and_use_detected_mime_extension(): void
    {
        $imagePath = $this->createPng(1, 2);
        $service = new class($this->mockModel, $this->avatarValidations(), 'avatar_file') extends ProfileImages {
            protected function updateFile(): bool
            {
                return true;
            }
        };

        $result = $service->update([
            'name' => 'icone.JPG',
            'tmp_name' => $imagePath,
            'size' => filesize($imagePath),
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertTrue($result);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}\\.png$/', $this->mockModel->avatar_file);
    }

    public function test_should_reject_corrupted_file_with_allowed_extension(): void
    {
        $imagePath = tempnam(sys_get_temp_dir(), 'avatar-corrupted-');
        file_put_contents($imagePath, 'not an image');
        $this->temporaryImages[] = $imagePath;
        $service = new ProfileImages($this->mockModel, $this->avatarValidations(), 'avatar_file');

        $result = $service->update([
            'name' => 'icone.PNG',
            'tmp_name' => $imagePath,
            'size' => filesize($imagePath),
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertFalse($result);
        $this->assertSame('Imagem inválida ou corrompida', $this->mockModel->errors('avatar_file'));
    }

    public function test_should_reject_images_exceeding_width_or_total_pixel_limits(): void
    {
        $widthExceeded = $this->createPngHeader(4097, 1);
        $pixelsExceeded = $this->createPngHeader(4001, 4000);
        $service = new ProfileImages($this->mockModel, $this->avatarValidations(), 'avatar_file');

        $this->assertFalse($service->update([
            'name' => 'wide.png',
            'tmp_name' => $widthExceeded,
            'size' => filesize($widthExceeded),
            'error' => UPLOAD_ERR_OK,
        ]));
        $this->assertStringContainsString('dimensões máximas', $this->mockModel->errors('avatar_file'));

        $this->mockModel->errorsArray = [];
        $this->assertFalse($service->update([
            'name' => 'many-pixels.png',
            'tmp_name' => $pixelsExceeded,
            'size' => filesize($pixelsExceeded),
            'error' => UPLOAD_ERR_OK,
        ]));
        $this->assertStringContainsString('16000000 pixels', $this->mockModel->errors('avatar_file'));
    }

    public function test_should_preserve_previous_avatar_when_persistence_fails(): void
    {
        $this->mockModel->avatar_file = 'previous.png';
        $this->mockModel->shouldUpdate = false;
        $imagePath = $this->createPng(2, 1);
        $service = new class($this->mockModel, $this->avatarValidations(), 'avatar_file') extends ProfileImages {
            protected function updateFile(): bool
            {
                return true;
            }
        };

        $result = $service->update([
            'name' => 'replacement.png',
            'tmp_name' => $imagePath,
            'size' => filesize($imagePath),
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertFalse($result);
        $this->assertTrue($service->hasInternalFailure());
        $this->assertSame('previous.png', $this->mockModel->avatar_file);
    }

    /** @return array<string, mixed> */
    private function avatarValidations(): array
    {
        return [
            'extensions' => ['jpg', 'jpeg', 'png'],
            'mime_types' => ['image/jpeg', 'image/png'],
            'max_size' => 5 * 1024 * 1024,
            'dimensions' => [
                'max_width' => 4096,
                'max_height' => 4096,
                'max_pixels' => 16000000,
            ],
        ];
    }

    private function createPng(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar-image-');
        $image = imagecreatetruecolor($width, $height);
        imagepng($image, $path);
        imagedestroy($image);
        $this->temporaryImages[] = $path;

        return $path;
    }

    private function createPngHeader(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar-header-');
        $header = "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);
        file_put_contents($path, $header . pack('N', crc32(substr($header, 12))));
        $this->temporaryImages[] = $path;

        return $path;
    }
}
