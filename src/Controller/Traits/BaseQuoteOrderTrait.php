<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use App\Exchange\Market;

trait BaseQuoteOrderTrait
{
    public function reverseBaseQuote(Market $market): Market
    {
        $fixedMarket = clone $market;
        $fixedMarket->setBase($market->getQuote());
        $fixedMarket->setQuote($market->getBase());
        return $fixedMarket;
    }
}
