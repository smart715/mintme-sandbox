<?php declare(strict_types = 1);

namespace App\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NumberTruncateWithLetterExtension extends AbstractExtension
{
    private const BILLION_NUMBER = 1_000_000_000;
    private const MILLION_NUMBER = 1_000_000;
    private const THOUSAND_NUMBER = 1_000;
    private const BILLION_LETTER = 'B';
    private const MILLION_LETTER = 'M';
    private const THOUSAND_LETTER = 'K';

    public function getFilters(): array
    {
        return [
            new TwigFilter('number_truncate_with_letter', [$this, 'doNumberTruncateWithLetter']),
        ];
    }

    /**
     * @param string|int|float $number
     * @return string|null
     */
    public function doNumberTruncateWithLetter($number): ?string
    {
        if (self::BILLION_NUMBER <= $number) {
            return substr((string)((float)$number / self::BILLION_NUMBER), 0, 5) . self::BILLION_LETTER;
        }

        if (self::MILLION_NUMBER <= $number) {
            return substr((string)((float)$number / self::MILLION_NUMBER), 0, 5) . self::MILLION_LETTER;
        }

        if (self::THOUSAND_NUMBER <= $number) {
            return substr((string)((float)$number / self::THOUSAND_NUMBER), 0, 5) . self::THOUSAND_LETTER;
        }

        return number_format(round((float)$number, 4, PHP_ROUND_HALF_DOWN), 4);
    }
}
