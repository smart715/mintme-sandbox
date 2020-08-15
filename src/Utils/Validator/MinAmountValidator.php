<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;

class MinAmountValidator implements ValidatorInterface
{
    /** @var TradebleInterface|null */
    private $tradable;

    /** @var string */
    private $amount;

    /** @var string */
    private $message;

    public function __construct(
        TradebleInterface $tradable,
        string $amount
    ) {
        $this->tradable = $tradable;
        $this->amount = $amount;
    }

    public function validate(): bool
    {
        $unit = $this->tradable instanceof Crypto
            ? $this->tradable->getShowSubunit()
            : Token::TOKEN_SUBUNIT;

        $min = $unit > 0
            ? $this->getMinimal($unit)
            : 0;

        $minAmountDecimal = rtrim(sprintf('%.18f', $min), '0');
        $this->message = "Minimum amount is {$minAmountDecimal} {$this->tradable->getSymbol()}";

        return (float)$this->amount >= $min;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit + 1, '0');
    }
}
