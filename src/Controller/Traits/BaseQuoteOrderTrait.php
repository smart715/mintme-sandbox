<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use App\Exchange\Market;

trait BaseQuoteOrderTrait
{
    public function fixBaseQuoteOrder(Market $market)
    {
        if ($market->isTokenMarket()) {
            $base = $market->getBase();
            $quote = $market->getQuote();
            $market->setBase($quote);
            $market->setQuote($base);
        }
    }
}
