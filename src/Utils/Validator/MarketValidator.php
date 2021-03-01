<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Market;

class MarketValidator implements ValidatorInterface
{
    private Market $market;

    /** @var string */
    private $message = 'Invalid Market';

    public function __construct(Market $market)
    {
        $this->market = $market;
    }

    public function validate(): bool
    {
        $base = $this->market->getBase();
        $quote = $this->market->getQuote();

        return $base instanceof Crypto && $base->getSymbol() !== $quote->getSymbol()
            && (
                Token::WEB_SYMBOL === $quote->getSymbol()
                ||
                ($quote instanceof Token && Token::WEB_SYMBOL === $base->getSymbol())
            );
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
