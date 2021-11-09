<?php declare(strict_types = 1);

namespace App\Entity;

interface ImagineInterface
{
    public function setImage(Image $image): void;
    public function getImage(): ?Image;
}
