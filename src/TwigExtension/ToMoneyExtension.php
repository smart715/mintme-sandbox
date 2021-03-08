<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ToMoneyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('toMoney', [$this, 'toMoney']),
        ];
    }

    public function toMoney(string $value, int $precision = 2, bool $fixedPoint = true): ?string
    {
        $decimalFactor = (int)str_pad('1', $precision + 1, '0');
        $number = number_format(
            floor((float)$value * $decimalFactor) / $decimalFactor,
            $precision,
            '.',
            ' '
        );

        return $fixedPoint
            ? $number
            : preg_replace('/(\.[1-9]+){0,1}(\.*0+)$/', '$1', $number);
    }
}
