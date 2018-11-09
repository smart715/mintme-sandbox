<?php

namespace App\Utils;

interface MarketNameParserInterface
{
    public function parseSymbol($market): string;

    public function parseName($market): string;
}