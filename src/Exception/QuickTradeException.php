<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore */
class QuickTradeException extends \Exception
{
    // translation keys
    public const INVALID_MODE_KEY = 'quick_trade.invalid_mode';
    public const INVALID_CURRENCY_KEY = 'quick_trade.invalid_currency';
    public const AVAILABILITY_CHANGED_KEY = 'quick_trade.availability_changed';
    public const MIN_AMOUNT_VALIDATOR_KEY = 'quick_trade.min_amount_validator';
    public const INSUFFICIENT_BALANCE_KEY = 'quick_trade.insufficient_balance';
    public const NOT_ENOUGH_ORDERS_KEY = 'quick_trade.donation.not_enough_orders';

    private string $key;
    private array $context;

    private function __construct(string $key, array $context = [])
    {
        $this->key = $key;
        $this->context = $context;

        parent::__construct();
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function invalidMode(): self
    {
        return new QuickTradeException(self::INVALID_MODE_KEY);
    }

    public static function invalidCurrency(): self
    {
        return new QuickTradeException(self::INVALID_CURRENCY_KEY);
    }

    public static function insufficientBalance(): self
    {
        return new QuickTradeException(self::INSUFFICIENT_BALANCE_KEY);
    }

    public static function availabilityChanged(): self
    {
        return new QuickTradeException(self::AVAILABILITY_CHANGED_KEY);
    }

    public static function minAmountValidator(string $message): self
    {
        return new QuickTradeException(self::MIN_AMOUNT_VALIDATOR_KEY, ['%validatorMessage%' => $message]);
    }

    public static function notEnoughOrders(): self
    {
        return new QuickTradeException(self::NOT_ENOUGH_ORDERS_KEY);
    }
}
