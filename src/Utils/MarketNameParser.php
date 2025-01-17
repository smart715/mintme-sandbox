<?php declare(strict_types = 1);

namespace App\Utils;

class MarketNameParser implements MarketNameParserInterface
{

    public function parseSymbol(string $market): string
    {
        return substr($market, -3);
    }

    public function parseName(string $market): string
    {
        return substr($market, 0, -3);
    }
}
