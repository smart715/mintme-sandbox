<?php declare(strict_types = 1);

namespace App\Manager;

interface MainDocumentsManagerInterfaces
{
    public function findDocPathByName(string $name): ?string;
}
