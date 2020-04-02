<?php declare(strict_types = 1);

namespace App\Utils;

use App\Exchange\Market;
use App\Utils\Validator\MinOrderValidator;
use App\Utils\Validator\ValidatorInterface;

/** @codeCoverageIgnore */
class ValidatorFactory implements ValidatorFactoryInterface
{
    public function createOrderValidator(Market $market, string $price, string $amount): ValidatorInterface
    {
        return new MinOrderValidator($market->getBase(), $market->getQuote(), $price, $amount);
    }
}
