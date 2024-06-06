<?php declare(strict_types = 1);

namespace App\Utils;

use App\Exchange\Market;

class BaseQuote
{
    public static function reverse(string $base, string $quote): array
    {
        $temp = $base;
        $base = $quote;
        $quote = $temp;

        return [$base, $quote];
    }

    public static function reverseMarket(Market $market): Market
    {
        $temp = $market->getBase();
        $market->setBase($market->getQuote());
        $market->setQuote($temp);

        return $market;
    }

    public static function reverseMarketInPlace(Market $market): Market
    {
        return new Market($market->getQuote(), $market->getBase());
    }
}
