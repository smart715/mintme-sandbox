<?php declare(strict_types = 1);

namespace App\Controller\Traits;

use App\Exchange\Market;

trait BaseQuoteOrderTrait
{
    public function reverseBaseQuote(Market $market): Market
    {
        $reversed = clone $market;
        $reversed->setBase($market->getQuote());
        $reversed->setQuote($market->getBase());

        return $reversed;
    }
}
