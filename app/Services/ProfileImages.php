<?php

namespace App\Services;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use PDO;
use RuntimeException;

class ProfileImages
{
    /**
     * @var array<string, mixed>
     */
    private array $image;
    private ?string $generatedFileName = null;
    private ?string $detectedMimeType = null;
    private bool $internalFailure = false;

    /**
     * @param Model $model
     * @param array<string, mixed> $validations
     * @param string $column
     */
    public function __construct(
        private Model $model,
        private array $validations = [],
        private string $column = 'avatar_file'
    ) {
    }

    public function path(): string
    {
        if ($this->model->{$this->column}) {
            $absolutePath = $this->getAbsoluteSavedFilePath();
            if (file_exists($absolutePath)) {
                $hash = md5_file($absolutePath);
                return $this->baseDir() . $this->model->{$this->column} . '?' . $hash;
            }
        }

        $defaultImageName = str_replace('_file', '', $this->column);
        return "/assets/images/defaults/{$defaultImageName}.png";
    }

    /**
     * @param array<string, mixed> $image
     * @return bool
     */
    public function update(array $image): bool
    {
        $this->image = $image;
        $this->generatedFileName = null;
        $this->detectedMimeType = null;
        $this->internalFailure = false;

        if (!$this->isValidImage()) {
            return false;
        }

        $oldFileName = $this->model->{$this->column};
        $newFileName = $this->getFileName();

        if (!$this->updateFile()) {
            return false;
        }

        try {
            if ($this->model->update([$this->column => $newFileName])) {
                $this->removeImage($oldFileName);
                return true;
            }

            $this->removeImage($newFileName);
            $this->model->{$this->column} = $oldFileName;
            $this->internalFailure = true;
            return false;
        } catch (\Throwable) {
            $this->removeImage($newFileName);
            $this->model->{$this->column} = $oldFileName;
            $this->internalFailure = true;
            return false;
        }
    }

    public function hasInternalFailure(): bool
    {
        return $this->internalFailure;
    }

    public function delete(): bool
    {
        $this->removeOldImage();
        return $this->model->update([$this->column => null]);
    }

    protected function updateFile(): bool
    {

        if (empty($this->image['tmp_name'])) {
            return false;
        }

        $resp = move_uploaded_file(
            $this->image['tmp_name'],
            $this->getAbsoluteDestinationPath()
        );

        if (!$resp) {
            $error = error_get_last();
            throw new RuntimeException(
                'Failed to move uploaded file: ' . ($error['message'] ?? 'Unknown error')
            );
        }

        return true;
    }

    private function removeOldImage(): void
    {
        $this->removeImage($this->model->{$this->column});
    }

    private function removeImage(?string $fileName): void
    {
        if ($fileName) {
            $path = $this->getAbsoluteSavedFilePath($fileName);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function getFileName(): string
    {
        if (!$this->generatedFileName) {
            $file_extension = $this->extensionForSavedFile();
            $this->generatedFileName = bin2hex(random_bytes(8)) . '.' . $file_extension;
        }
        return $this->generatedFileName;
    }

    private function extensionForSavedFile(): string
    {
        return match ($this->detectedMimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            default => strtolower((string) pathinfo($this->image['name'], PATHINFO_EXTENSION)),
        };
    }

    private function getAbsoluteDestinationPath(): string
    {
        return $this->storeDir() . $this->getFileName();
    }

    private function baseDir(): string
    {
        return "/assets/uploads/{$this->model::table()}/{$this->model->id}/";
    }

    private function storeDir(): string
    {
        $path = Constants::rootPath()->join('public' . $this->baseDir());

        if (!is_dir($path)) {
            mkdir(directory: $path, recursive: true);
        }

        return $path;
    }

    private function getAbsoluteSavedFilePath(?string $fileName = null): string
    {
        return Constants::rootPath()->join('public' . $this->baseDir())->join($fileName ?? $this->model->{$this->column});
    }

    private function isValidImage(): bool
    {
        if (isset($this->validations['extensions'])) {
            $this->validateImageExtension();
        }

        if (isset($this->validations['max_size'])) {
            $this->validateImageSize();
        }

        if (isset($this->validations['mime_types']) || isset($this->validations['dimensions']) || isset($this->validations['aspect_ratio'])) {
            $this->validateImageMetadata();
        }

        return $this->model->errors($this->column) === null;
    }

    private function validateImageExtension(): void
    {
        $file_extension = strtolower((string) pathinfo($this->image['name'], PATHINFO_EXTENSION));

        if (!in_array($file_extension, $this->validations['extensions'])) {
            $this->model->addError(
                $this->column,
                'A Imagem deve ser um arquivo do tipo: ' . implode(', ', $this->validations['extensions'])
            );
        }
    }

    private function validateImageSize(): void
    {
        if ($this->image['size'] > $this->validations['max_size']) {
            $this->model->addError(
                $this->column,
                'A imagem excede o tamanho máximo permitido de ' . ($this->validations['max_size'] / (1024 * 1024)) . ' MB'
            );
        }
    }

    private function validateImageMetadata(): void
    {
        $imageInfo = getimagesize($this->image['tmp_name']);

        if (!$imageInfo) {
            $this->model->addError($this->column, 'Imagem inválida ou corrompida');
            return;
        }

        $this->detectedMimeType = $imageInfo['mime'] ?? null;
        if (isset($this->validations['mime_types']) && !in_array($this->detectedMimeType, $this->validations['mime_types'], true)) {
            $this->model->addError($this->column, 'A imagem deve ser do tipo: ' . implode(', ', $this->validations['mime_types']));
            return;
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        if ($width <= 0 || $height <= 0) {
            $this->model->addError($this->column, 'A imagem deve ter dimensões válidas');
            return;
        }

        if (isset($this->validations['dimensions'])) {
            $dimensions = $this->validations['dimensions'];
            if (
                $width > $dimensions['max_width'] ||
                $height > $dimensions['max_height'] ||
                $width * $height > $dimensions['max_pixels']
            ) {
                $this->model->addError(
                    $this->column,
                    'A imagem excede as dimensões máximas permitidas de ' .
                    $dimensions['max_width'] . 'x' . $dimensions['max_height'] .
                    ' pixels ou ' . $dimensions['max_pixels'] . ' pixels no total'
                );
                return;
            }
        }

        if (isset($this->validations['mime_types']) && !$this->isDecodableImage()) {
            $this->model->addError($this->column, 'Imagem inválida ou corrompida');
            return;
        }

        if (isset($this->validations['aspect_ratio'])) {
            $aspectRatio = $width / $height;
            if (
                $aspectRatio < $this->validations['aspect_ratio']['min'] || $aspectRatio >
                $this->validations['aspect_ratio']['max']
            ) {
                $this->model->addError(
                    $this->column,
                    'A proporção da imagem deve estar entre ' .
                    $this->validations['aspect_ratio']['min'] . ' e ' .
                    $this->validations['aspect_ratio']['max']
                );
            }
        }
    }

    private function isDecodableImage(): bool
    {
        if ($this->detectedMimeType === 'image/jpeg') {
            if (!function_exists('imagecreatefromjpeg')) {
                throw new RuntimeException('Extensão GD não está disponível para validar imagens JPEG');
            }

            $decodedImage = @imagecreatefromjpeg($this->image['tmp_name']);
        } elseif ($this->detectedMimeType === 'image/png') {
            if (!function_exists('imagecreatefrompng')) {
                throw new RuntimeException('Extensão GD não está disponível para validar imagens PNG');
            }

            $decodedImage = @imagecreatefrompng($this->image['tmp_name']);
        } else {
            return false;
        }

        if ($decodedImage === false) {
            return false;
        }

        imagedestroy($decodedImage);
        return true;
    }
}
