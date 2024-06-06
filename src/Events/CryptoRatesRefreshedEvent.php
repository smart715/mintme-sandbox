<?php declare(strict_types = 1);

namespace App\Events;

use Symfony\Contracts\EventDispatcher\Event;

/** @codeCoverageIgnore */
class CryptoRatesRefreshedEvent extends Event
{
    public const NAME = "crypto.rates.refreshed";

    protected array $rates;

    public function __construct(array $rates)
    {
        $this->rates = $rates;
    }

    public function getRates(): array
    {
        return $this->rates;
    }
}
