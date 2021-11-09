<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\MarketStatus;
use App\Exchange\AbstractOrder;
use App\Exchange\MarketInfo;

interface RebrandingConverterInterface
{
    public function convert(string $value): string;
    public function reverseConvert(string $value): string;
    public function convertMarketStatus(MarketStatus $market): MarketStatus;
    public function convertMarketInfo(MarketInfo $market): MarketInfo;
    public function convertOrder(AbstractOrder $order): AbstractOrder;
}
