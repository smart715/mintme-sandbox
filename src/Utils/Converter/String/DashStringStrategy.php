<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

/***
 * Class DashStringStrategy
 *
 * - Convert all spaces to dashes
 *
 * - Trim all spaces and dashes in string
 * - reduce contiguous spaces to 1 space
 * - convert mixed spaces and dashed to 1 dash
 *
 * @package App\Utils\Converter
 */
class DashStringStrategy implements StringConverterInterface
{

    public function convert(?string $tokenName): string
    {
        return str_replace(' ', '-', (new StringConverter(new ParseStringStrategy()))
            ->convert($tokenName));
    }
}
