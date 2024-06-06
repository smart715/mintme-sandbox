<?php declare(strict_types = 1);

namespace App\Exception;

class CryptoCalculatorException extends \Exception
{
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

    public static function notEnoughOrders(): self
    {
        return new CryptoCalculatorException(self::NOT_ENOUGH_ORDERS_KEY);
    }
}
