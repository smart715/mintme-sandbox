<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use App\Exchange\Market;

trait BaseQuoteOrder
{
    public function fixBaseQuoteOrder(Market $market): Market
    {
        // TODO: when #6477 is done this should be changed accordingly
        if ($market->isTokenMarket()) {
            $base = $market->getQuote();
            $quote = $market->getBase();
        } else {
            $base = $market->getBase();
            $quote = $market->getQuote();
        }

        $market->setBase($base);
        $market->setQuote($quote);

        return $market;
    }
}
