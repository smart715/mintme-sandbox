<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

/***
 * Class DashStringStrategy
 *
 * - Convert all spaces to dashes of ParseStringStrategy
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
