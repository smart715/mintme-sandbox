<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradableInterface;
use App\Utils\Converter\RebrandingConverter;
use App\Utils\Symbols;

class MinAmountValidator implements ValidatorInterface
{
    private ?TradableInterface $tradable;
    private string $amount;
    private string $message;

    public function __construct(
        TradableInterface $tradable,
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

        $rebrandingConverter = new RebrandingConverter();

        $currency = Symbols::WEB === $this->tradable->getSymbol()
            ? $rebrandingConverter->convert($this->tradable->getSymbol())
            : $this->tradable->getSymbol();

        $minAmountDecimal = rtrim(sprintf('%.18f', $min), '0');
        $this->message = "Minimum amount is {$minAmountDecimal} {$currency}";

        return (float)$this->amount >= $min;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit, '0');
    }
}
