<?php

namespace App\Utils;


class MarketNameParser implements MarketNameParserInterface
{

    public function parseSymbol($market): string
    {
        return substr($market, -3);
    }

    public function parseName($market): string
    {
        return substr($market, 3, -3);
    }
}