<?php declare(strict_types = 1);

namespace App\Config;

/** @codeCoverageIgnore */
class TokenPromotionConfig
{
    private array $tariffs;
    private array $tariffsMap;

    public function __construct(
        array $tariffs
    ) {
        $this->tariffs = $tariffs;
        $this->tariffsMap = array_reduce($tariffs, function ($acc, $tariff) {
            $acc[$tariff['duration']] = $tariff;

            return $acc;
        }, []);
    }

    public function getTariff(string $tariffDuration): ?array
    {
        return $this->tariffsMap[$tariffDuration] ?? null;
    }

    public function getTariffs(): array
    {
        return $this->tariffs;
    }
}
