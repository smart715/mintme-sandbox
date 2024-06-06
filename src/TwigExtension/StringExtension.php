<?php declare(strict_types = 1);

namespace App\TwigExtension;

use App\Utils\Converter\String\SpaceConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StringExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('dashedString', [$this, 'dashedString']),
        ];
    }

    public function dashedString(string $name): string
    {
        return (new SpaceConverter())->toDash($name);
    }
}
