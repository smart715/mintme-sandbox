<?php

namespace App\Utils;

interface MarketNameParserInterface
{
    public function parseSymbol(string $market): string;

    public function parseName(string $market): string;
}
