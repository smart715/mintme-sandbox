<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;

class MarketNameConverter implements MarketNameConverterInterface
{
    /** @var TokenNameConverterInterface */
    private $tokenConverter;

    public function __construct(TokenNameConverterInterface $converter)
    {
        $this->tokenConverter = $converter;
    }

    public function convert(Market $market, bool $baseFirst = false): string
    {
        $base = $this->convertTradable($market->getBase());
        $quote = $this->convertTradable($market->getQuote());

        return strtoupper($baseFirst ? $base . $quote : $quote . $base);
    }

    private function convertTradable(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token ?
            $this->tokenConverter->convert($tradeble) :
            $tradeble->getSymbol();
    }
}
