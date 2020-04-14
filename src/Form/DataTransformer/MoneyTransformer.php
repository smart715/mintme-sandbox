<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class MoneyTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value): string
    {
        return $value->getAmount();
    }
    
    /** @inheritdoc */
    public function reverseTransform($value): Money
    {
        return new Money($value, new Currency('TOK'));
    }
}
