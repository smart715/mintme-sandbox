<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use Doctrine\ORM\EntityRepository;

interface SlugConverterInterface
{
    public function convert(string $from, EntityRepository $repository, $propertyName = 'slug'): string;
}
