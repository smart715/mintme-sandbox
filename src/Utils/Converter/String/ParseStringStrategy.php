<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

/***
 * Class DashStringStrategy
 *
 * - Trim all spaces and dashes in string
 * - reduce contiguous spaces to 1 space
 * - convert mixed spaces and dashed to 1 dash
 *
 * @package App\Utils\Converter
 */

class ParseStringStrategy implements StringConverterInterface
{
    public function convert(?string $tokenName): string
    {
        return (string)preg_replace(
            ['/\s+/', '/\s*\-{1,}\s*/', '/\-+\s*\-+/'],
            [' ', '-', '-'],
            trim($tokenName ?? '', ' -')
        );
    }
}
