<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileImageUploader
{
    private const MAX_FILE_SIZE = 2097152;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public function __construct(
        private readonly string $projectDir,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function validate(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return '❌ Le fichier sélectionné est invalide.';
        }

        if (($file->getSize() ?? 0) > self::MAX_FILE_SIZE) {
            return '❌ La photo de profil ne doit pas dépasser 2 Mo.';
        }

        if (!in_array((string) $file->getMimeType(), self::ALLOWED_MIME_TYPES, true)) {
            return '❌ Formats acceptés: JPG, PNG, WEBP ou GIF.';
        }

        return null;
    }

    public function upload(UploadedFile $file, ?string $oldFilename = null): string
    {
        $uploadDirectory = $this->getUploadDirectory();

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename !== '' ? $originalFilename : 'profil')->lower();
        $extension = $file->guessExtension() ?: 'bin';
        $newFilename = sprintf('%s-%s.%s', $safeFilename, bin2hex(random_bytes(6)), $extension);

        $file->move($uploadDirectory, $newFilename);

        if ($oldFilename !== null && $oldFilename !== '' && $oldFilename !== $newFilename) {
            $this->remove($oldFilename);
        }

        return $newFilename;
    }

    public function remove(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $filePath = $this->getUploadDirectory() . DIRECTORY_SEPARATOR . $filename;

        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    private function getUploadDirectory(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
    }
}
