<?php declare(strict_types = 1);

namespace App\Utils\Validator;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Wallet\Money\MoneyWrapper;
use Brick\Math\BigDecimal;

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

    /** @var string */
    private $message = 'Amount is low';

    private string $minimalPriceOrder;

    public function __construct(
        ?TradebleInterface $baseTradable,
        ?TradebleInterface $quoteTradable,
        string $price,
        string $amount,
        string $minimalPriceOrder
    ) {
        $this->baseTradable = $baseTradable;
        $this->quoteTradable = $quoteTradable;
        $this->price = $price;
        $this->amount = $amount;
        $this->minimalPriceOrder =$minimalPriceOrder;
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

        $scale = $baseUnit > $quoteUnit
            ? $baseUnit
            : $quoteUnit;

        $baseMinimal = $baseUnit > 0
            ? $this->getMinimal($baseUnit)
            : 0;

        $quoteMinimal = $quoteUnit > 0
            ? $this->getMinimal($quoteUnit)
            : 0;

        return $this->price >= $baseMinimal
            && $this->amount >= $quoteMinimal
            && $this->checkOrderPriceMinimal($scale);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    private function getCrypto(?TradebleInterface $tradeble): ?Crypto
    {
        if ($tradeble instanceof Token) {
            /** @var Token $token */
            $token = $tradeble;

            return $token->getCrypto();
        }

        /** @var Crypto|null $crypto */
        $crypto = $tradeble;

        return $crypto;
    }

    private function getMinimal(int $unit): float
    {
        return 1 / (int)str_pad('1', $unit + 1, '0');
    }

    private function checkOrderPriceMinimal(int $scale): bool
    {
        $totalOrderAmount = BigDecimal::of((float)$this->price)
            ->toScale($scale)
            ->multipliedBy(
                BigDecimal::of((float)$this->amount)->toScale($scale)
            )->toFloat();

        // todo parse to USD using MoneyWarapper to compare

        dd($totalOrderAmount);

    }
}
