<?php declare(strict_types = 1);

namespace App\Communications\GeckoCoin\Model;

class SimplePrice
{
    private array $ids;
    private array $vsCurrencies;
    private ?bool $includeMarketCap;
    private ?bool $include24HoursVol;
    private ?bool $include24HoursChange;
    private ?bool $includeLastUpdatedAt;

    public function __construct(
        array $ids = [],
        array $vsCurrencies = [],
        ?bool $includeMarketCap = null,
        ?bool $include24HoursVol = null,
        ?bool $include24HoursChange = null,
        ?bool $includeLastUpdatedAt = null
    ) {
        $this->ids = $ids;
        $this->vsCurrencies = $vsCurrencies;
        $this->includeMarketCap = $includeMarketCap;
        $this->include24HoursVol = $include24HoursVol;
        $this->include24HoursChange = $include24HoursChange;
        $this->includeLastUpdatedAt = $includeLastUpdatedAt;
    }

    public function getQueriesString(): string
    {
        $data = [];

        if ([] !== $this->ids) {
            $data[] = 'ids=' . implode(',', $this->ids);
        }

        if ([] !== $this->vsCurrencies) {
            $data[] = 'vs_currencies=' . implode(',', $this->vsCurrencies);
        }

        if (null !== $this->include24HoursChange) {
            $data[] = 'include_24hr_change=' . $this->include24HoursChange;
        }

        if (null !== $this->include24HoursVol) {
            $data[] = 'include_24hr_vol=' . $this->include24HoursVol;
        }

        if (null !== $this->includeMarketCap) {
            $data[] = 'include_market_cap=' . $this->includeMarketCap;
        }

        if (null !== $this->includeLastUpdatedAt) {
            $data[] = 'include_last_updated_at=' . $this->includeLastUpdatedAt;
        }

        return implode('&', $data);
    }
}
