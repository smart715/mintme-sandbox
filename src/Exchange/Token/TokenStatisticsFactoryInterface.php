<?php declare(strict_types = 1);

namespace App\Exchange\Token;

use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Exchange\Token\Model\TokenStatisticsModel;

interface TokenStatisticsFactoryInterface
{
    public function create(Token $token, Market $market): TokenStatisticsModel;
}
