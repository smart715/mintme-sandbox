<?php declare(strict_types = 1);

namespace App\Exception;

/** @codeCoverageIgnore  */
class QuickTradeException extends \Exception
{
    // translation keys
    public const INVALID_MODE_KEY = 'quick_trade.invalid_mode';
    public const AVAILABILITY_CHANGED_KEY = 'quick_trade.availability_changed';

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

    public static function invalidMode(string $mode): self
    {
        return new QuickTradeException(self::INVALID_MODE_KEY, ['%mode%' => $mode]);
    }

    public static function availabilityChanged(): self
    {
        return new QuickTradeException(self::AVAILABILITY_CHANGED_KEY);
    }
}
