<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\MarketStatus;
use App\Exchange\AbstractOrder;
use App\Exchange\MarketInfo;

class RebrandingConverter implements RebrandingConverterInterface
{
    public function convert(string $value): string
    {
        $regExp =   ['/(Webchain)/', '/(webchain)/', '/(WEB)/', '/(web)/'];
        $replacer = ['MintMe Coin',  'mintMe Coin',  'MINTME',  'MINTME'];

        return (string) preg_replace($regExp, $replacer, $value);
    }

    public function reverseConvert(string $value): string
    {
        $regExp =   ['/(MintMe Coin)/',  '/(mintMe Coin)/', '/(^|\s)(mintme)(\s|$)/', '/(^|\s)(MINTME)(\s|$)/'];
        $replacer = ['Webchain', 'webchain', '$1WEB$3', '$1WEB$3'];

        return (string) preg_replace($regExp, $replacer, $value);
    }

    public function convertMarketStatus(MarketStatus $market): MarketStatus
    {
        $base = $market->getCrypto();
        $base->setName($this->convert($base->getName()));
        $base->setSymbol($this->convert($base->getSymbol()));
        $market->setCrypto($base);
        $quote = $market->getQuote();
        $quote->setName($this->convert($quote->getName()));
        $quote->setSymbol($this->convert($quote->getSymbol()));
        $market->setQuote($quote);

        return $market;
    }

    public function convertMarketInfo(MarketInfo $market): MarketInfo
    {
        $base = $market->getCryptoSymbol();
        $base = $this->convert($base);
        $market->setCryptoSymbol($base);
        $quote = $market->getTokenName();
        $quote = $this->convert($quote);
        $market->setTokenName($quote);

        return $market;
    }

    public function convertOrder(AbstractOrder $order): AbstractOrder
    {
        $market = $order->getMarket();
        $base = $market->getBase();
        $base->setName($this->convert($base->getName()));
        $base->setSymbol($this->convert($base->getSymbol()));
        $market->setBase($base);
        $quote = $market->getQuote();
        $quote->setName($this->convert($quote->getName()));
        $quote->setSymbol($this->convert($quote->getSymbol()));
        $market->setQuote($quote);
        $order->setMarket($market);

        return $order;
    }
}
