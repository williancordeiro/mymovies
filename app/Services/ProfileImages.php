<?php

namespace App\Services;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use PDO;
use RuntimeException;

class ProfileImages {

    private array $image;
    private ?string $generatedFileName = null;

    public function __construct(
        private Model $model,
        private array $validations = [],
        private string $column = 'avatar_file'
    ) {}

    public function path(): string {
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

    public function update(array $image): bool {
        $this->image = $image;

        if (!$this->isValidImage()) {
            return false;
        }

        if ($this->updateFile()) {
            $this->model->update([
                $this->column => $this->getFileName(),
            ]);

            return true;
        }

        return false;
    }

    protected function updateFile(): bool {
        
        if (empty($this->image['tmp_name'])) {
            return false;
        }

        $this->removeOldImage();

        $resp = move_uploaded_file(
            $this->image['tmp_name'],
            $this->getAbsoluteDestinationPath()
        );

        if (!$resp) {
            $error = error_get_last();
            throw new RuntimeException(
                "Failed to move uploaded file: " . ($error['message'] ?? 'Unknown error')
            );
        }

        return true;
    }

    private function removeOldImage(): void {
        if ($this->model->{$this->column}) {
            $path = $this->getAbsoluteSavedFilePath();
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function getFileName(): string {
        $file_name_splitted = explode('.', $this->image['name']);
        $file_extension = end($file_name_splitted);
        if (!$this->generatedFileName) {
            $this->generatedFileName = bin2hex(random_bytes(8)) . '.' . $file_extension;
        }
        return $this->generatedFileName;
    }

    private function getAbsoluteDestinationPath(): string {
        return $this->storeDir() . $this->getFileName();
    }

    private function baseDir(): string {
        return "/assets/uploads/{$this->model::table()}/{$this->model->id}/";
    }

    private function storeDir(): string {
        $path = Constants::rootPath()->join('public' . $this->baseDir());
        
        if (!is_dir($path)) {
            mkdir(directory: $path, recursive: true);
        }

        return $path;
    }

    private function getAbsoluteSavedFilePath(): string {
       return Constants::rootPath()->join('public' . $this->baseDir())->join($this->model->{$this->column});
    }

    private function isValidImage(): bool {
        if (isset($this->validations['extensions'])) {
            $this->validateImageExtension();
        }

        if (isset($this->validations['max_size'])) {
            $this->validateImageSize();
        }

        if (isset($this->validations['aspect_ratio'])) {
            $this->validateImageAspectRatio();
        }

        return $this->model->errors($this->column) === null;
    }

    private function validateImageExtension(): void {
        $file_name_splitted = explode('.', $this->image['name']);
        $file_extension = end($file_name_splitted);

        if (!in_array($file_extension, $this->validations['extensions'])) {
            $this->model->addError($this->column, 'Invalid file extension');
        }
    }

    private function validateImageSize(): void {
        if ($this->image['size'] > $this->validations['max_size']) {
            $this->model->addError($this->column, 'File size exceeds the maximum allowed');
        }
    }

    private function validateImageAspectRatio(): void {
        if (isset($this->validations['aspect_ratio'])) {
            list($width, $height) = getimagesize($this->image['tmp_name']);
            $aspectRatio = $width / $height;

            if ($aspectRatio < $this->validations['aspect_ratio']['min'] || $aspectRatio > $this->validations['aspect_ratio']['max']) {
                $this->model->addError($this->column, 'Invalid aspect ratio');
            }
        }
    }
}
?>