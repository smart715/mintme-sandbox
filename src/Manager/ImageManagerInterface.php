<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ImageManagerInterface
{
    public function upload(UploadedFile $file, string $filter): Image;

    public function getWebDir(): string;
    public function getFileDir(): string;
}
