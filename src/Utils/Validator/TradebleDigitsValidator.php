<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;

class TradebleDigitsValidator implements ValidatorInterface
{
    private string $message;
    private string $amount;
    private TradebleInterface $tradeble;

    public function __construct(string $amount, TradebleInterface $tradeble)
    {
        $this->amount = $amount;
        $this->tradeble = $tradeble;
    }

    public function validate(): bool
    {
        $digits = 0;

        if ($this->tradeble instanceof Token) {
            $digits = null === $this->tradeble->getDecimals() || $this->tradeble->getDecimals() > Token::TOKEN_SUBUNIT
                ? Token::TOKEN_SUBUNIT
                : $this->tradeble->getDecimals();
        } elseif ($this->tradeble instanceof Crypto) {
            $digits = $this->tradeble->getShowSubunit();
        }

        $this->message = "Allowed digits is " . $digits;

        return strlen(substr($this->amount, strpos($this->amount, '.') + 1)) <= $digits;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
