<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserImage;

class GalleryImages
{
    /**
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * @param array<string, mixed> $validations
     */
    public function __construct(
        private User $user,
        private array $validations = []
    ) {
    }

    /**
     * @return UserImage[]
     */
    public function all(): array
    {
        /** @var UserImage[] */
        return $this->user->images()->get();
    }

    /**
     * @param array<string, mixed> $file
     */
    public function add(array $file): ?UserImage
    {
        $image = $this->user->images()->new(['image_file' => $file['name']]);

        /** @var UserImage $image */
        if (!$image->save()) {
            $this->errors = $image->errors();
            return null;
        }

        $service = new ProfileImages($image, $this->validations, 'image_file');

        if (!$service->update($file)) {
            $this->errors = $image->errors();
            $image->destroy();
            return null;
        }

        return $image;
    }

    public function remove(int $id): bool
    {
        $image = $this->user->images()->findById($id);
        if (!$image) {
            return false;
        }

        (new ProfileImages($image, [], 'image_file'))->delete();
        return $image->destroy();
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
