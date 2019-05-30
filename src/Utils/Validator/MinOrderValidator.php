<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;

class MinOrderValidator implements ValidatorInterface
{
    /** @var TradebleInterface|null */
    private $baseTradable;

    /** @var TradebleInterface|null */
    private $quoteTradable;

    /** @var string */
    private $price;

    /** @var string */
    private $amount;

    public function __construct(
        ?TradebleInterface $baseTradable,
        ?TradebleInterface $quoteTradable,
        string $price,
        string $amount
    ) {
        $this->baseTradable = $baseTradable;
        $this->quoteTradable = $quoteTradable;
        $this->price = $price;
        $this->amount = $amount;
    }

    public function validate(): bool
    {
        $base = $this->getCrypto($this->baseTradable);
        $quote = $this->getCrypto($this->quoteTradable);

        $baseUnit = $base
            ? $base->getShowSubunit()
            : 0;
        $quoteUnit = $quote
            ? $quote->getShowSubunit()
            : 0;

        if ($baseUnit > $quoteUnit) {
            bcscale($baseUnit);
        } else {
            bcscale($quoteUnit);
        }

        $baseMinimal = $baseUnit > 0
            ? $this->getMinimal($baseUnit)
            : 0;

        $quoteMinimal = $quoteUnit > 0
            ? $this->getMinimal($quoteUnit)
            : 0;

        return $this->price >= $baseMinimal
            && $this->amount >= $quoteMinimal
            && bcmul($this->price, $this->amount) >= $baseMinimal;
    }

    private function getCrypto(?TradebleInterface $tradeble): ?Crypto
    {
        if ($tradeble instanceof Token) {
            /** @var Token $toke */
            $token = $tradeble;

            return $token->getCrypto();
        }

        /** @var Crypto|null $crypto */
        $crypto = $tradeble;

        return $crypto;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / intval(str_pad('1', $unit + 1, '0'));
    }
}
