<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

class SpaceConverter
{
    public function toDash(string $name): string
    {
        return (new StringConverter(new DashStringStrategy()))->convert($name);
    }
}
