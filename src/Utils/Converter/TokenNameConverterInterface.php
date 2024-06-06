<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\TradableInterface;

interface TokenNameConverterInterface
{
    public function convert(TradableInterface $tradable): string;
    public function convertId(int $tokenId): string;
}
