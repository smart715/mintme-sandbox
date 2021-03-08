<?php declare(strict_types = 1);

namespace App\Exchange\Factory;

use App\Exchange\TradeInfo;

interface TradeFactoryInterface
{
    public function create(): TradeInfo;
}
