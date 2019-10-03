<?php declare(strict_types = 1);

namespace App\Utils;

use App\Exchange\Market;
use App\Utils\Validator\ValidatorInterface;

interface ValidatorFactoryInterface
{
    public function createOrderValidator(Market $market, string $price, string $amount): ValidatorInterface;
}
