<?php

namespace App\Utils\Converter;

use App\Exchange\Market;

class MarketNameConverter implements MarketNameConverterInterface
{
    /** @var TokenNameConverterInterface */
    private $tokenConverter;

    public function __construct(TokenNameConverterInterface $converter)
    {
        $this->tokenConverter = $converter;
    }

    public function convert(Market $market): string
    {
        $token = $market->getToken();

        if (!$token) {
            throw new \InvalidArgumentException();
        }

        return $this->tokenConverter->convert($token) . strtoupper($market->getCurrencySymbol());
    }
}
