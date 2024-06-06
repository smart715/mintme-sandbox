<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;

class TradableDigitsValidator implements ValidatorInterface
{
    private string $message;
    private string $amount;
    private TradableInterface $tradable;

    public function __construct(string $amount, TradableInterface $tradable)
    {
        $this->amount = $amount;
        $this->tradable = $tradable;
    }

    public function validate(): bool
    {
        $digits = 0;

        if ($this->tradable instanceof Token) {
            $digits = null === $this->tradable->getDecimals() || $this->tradable->getDecimals() > Token::TOKEN_SUBUNIT
                ? Token::TOKEN_SUBUNIT
                : $this->tradable->getDecimals();
        } elseif ($this->tradable instanceof Crypto) {
            $digits = $this->tradable->getShowSubunit();
        }

        $this->message = "Allowed digits is " . $digits;

        return strlen(substr($this->amount, strpos($this->amount, '.') + 1)) <= $digits
            || !strpos($this->amount, '.');
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
