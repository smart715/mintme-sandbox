<?php declare(strict_types = 1);

namespace App\Utils\Converter;

use App\Entity\Token\Token;
use App\Entity\TradebleInterface;

interface TokenNameConverterInterface
{
    public function convert(TradebleInterface $tradable): string;
}
