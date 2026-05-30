<?php

namespace App\Services;

use Core\Constants\Constants;
use Core\Database\ActiveRecord\Model;
use Core\Database\Database;
use PDO;
use RuntimeException;

class ProfileImages {

    private array $image;

    public function __construct(
        private Model $model,
        private array $validations = []
    ) {}

    public function path(): string {
        if ($this->model->avatar_file) {
            $absolutePath = $this->getAbsoluteSavedFilePath();
            if (file_exists($absolutePath)) {
                $hash = md5_file($absolutePath);
                return $this->baseDir() . $this->model->avatar_file . '?' . $hash;
            }
        }

        return "/assets/images/defaults/avatar.png";
    }

    public function update(array $image): bool {
        $this->image = $image;

        if (!$this->isValidImage()) {
            return false;
        }

        if ($this->updateFile()) {
            $this->model->update([
                'avatar_file' => $this->getFileName(),
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
        if ($this->model->avatar_file) {
            $path = $this->getAbsoluteSavedFilePath();
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function getFileName(): string {
        $file_name_splitted = explode('.', $this->image['name']);
        $file_extension = end($file_name_splitted);
        return 'avatar.' . $file_extension;
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
       return Constants::rootPath()->join('public' . $this->baseDir())->join($this->model->avatar_file);
    }

    private function isValidImage(): bool {
        if (isset($this->validations['extensions'])) {
            $this->validateImageExtension();
        }

        if (isset($this->validations['max_size'])) {
            $this->validateImageSize();
        }

        return $this->model->errors('avatar_file') === null;
    }

    private function validateImageExtension(): void {
        $file_name_splitted = explode('.', $this->image['name']);
        $file_extension = end($file_name_splitted);

        if (!in_array($file_extension, $this->validations['extensions'])) {
            $this->model->addError('avatar_file', 'Invalid file extension');
        }
    }

    private function validateImageSize(): void {
        if ($this->image['size'] > $this->validations['max_size']) {
            $this->model->addError('avatar_file', 'File size exceeds the maximum allowed');
        }
    }
}
?>