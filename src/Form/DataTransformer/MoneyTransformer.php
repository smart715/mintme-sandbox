<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use App\Entity\Token\Token;
use App\Wallet\Money\MoneyWrapperInterface;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class MoneyTransformer implements DataTransformerInterface
{
    /** @var MoneyWrapperInterface */
    private $moneyWrapper;

    /** @var string */
    private $symbol;

    public function __construct(MoneyWrapperInterface $moneyWrapper)
    {
        $this->moneyWrapper = $moneyWrapper;
    }

    /** @inheritdoc */
    public function transform($value): string
    {
        return $this->moneyWrapper->format($value);
    }
    
    /** @inheritdoc */
    public function reverseTransform($value): Money
    {
        try {
            return $this->moneyWrapper->parse($value, $this->symbol ?? Token::TOK_SYMBOL);
        } catch (\Throwable $e) {
            throw new TransformationFailedException();
        }
    }

    /**
     * Use this function on your FormType to set the symbol of the generated Money object
     */
    public function setSymbol(string $symbol): self
    {
        $this->symbol = $symbol;

        return $this;
    }
}
