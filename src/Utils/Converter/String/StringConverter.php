<?php declare(strict_types = 1);

namespace App\Utils\Converter\String;

class StringConverter
{
    /** @var StringConverterInterface */
    private $converter;

    public function __construct(StringConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    public function convert(?string $tokenName): string
    {
        return $this->converter->convert($tokenName);
    }
}
