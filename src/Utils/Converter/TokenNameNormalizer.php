<?php declare(strict_types = 1);

namespace App\Utils\Converter;

class TokenNameNormalizer implements TokenNameNormalizerInterface
{

    public function parse(string $tokenName): string
    {
        return (string)preg_replace(
            ['/\s+/', '/\s*\-{1,}\s*/'],
            [' ', '-'],
            trim($tokenName ?? '', ' -')
        );
    }

    public function dashed(string $tokenName): string
    {
        return str_replace(' ', '-', $this->parse($tokenName));
    }
}
