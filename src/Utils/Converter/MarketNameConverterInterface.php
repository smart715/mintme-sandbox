<?php

namespace App\Utils\Converter;

use App\Exchange\Market;

interface MarketNameConverterInterface
{
    public function convert(Market $market): string;
}
