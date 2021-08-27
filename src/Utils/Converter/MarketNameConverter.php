<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Exchange\Market;
use App\Utils\Symbols;

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
        $quote = $this->convertTradable($market->getQuote());
        $base = $market->getQuote() instanceof Token
            ? Symbols::WEB
            : $this->convertTradable($market->getBase());

        return strtoupper($baseFirst ? $base . $quote : $quote . $base);
    }

    private function convertTradable(TradebleInterface $tradeble): string
    {
        return $tradeble instanceof Token ?
            $this->tokenConverter->convert($tradeble) :
            $tradeble->getSymbol();
    }
}
