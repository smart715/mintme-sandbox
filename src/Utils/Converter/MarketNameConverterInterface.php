<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Exchange\Market;

interface MarketNameConverterInterface
{
    public function convert(Market $market): string;
}
