<?php declare(strict_types = 1);

namespace App\Exchange\Token\Model;

use App\Entity\Token\LockIn;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TokenStatisticsModel
{
    private ?Money $exchangeAmount;
    private ?string $totalPendingSellOrders;
    private ?string $totalPendingBuyOrders;
    private ?Money $sold;
    private ?Money $volumeDonation;
    private int $holders;

    public function __construct(
        ?Money $exchangeAmount = null,
        ?string $totalPendingSellOrders = null,
        ?string $totalPendingBuyOrders = null,
        ?Money $sold = null,
        ?Money $volumeDonation = null,
        ?int $holders = 0
    ) {
        $this->exchangeAmount = $exchangeAmount;
        $this->totalPendingSellOrders = $totalPendingSellOrders;
        $this->totalPendingBuyOrders = $totalPendingBuyOrders;
        $this->sold = $sold;
        $this->volumeDonation = $volumeDonation;
        $this->holders = $holders;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getExchangeAmount(): ?Money
    {
        return $this->exchangeAmount;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getTotalPendingSellOrders(): ?string
    {
        return $this->totalPendingSellOrders;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getTotalPendingBuyOrders(): ?string
    {
        return $this->totalPendingBuyOrders;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSold(): ?Money
    {
        return $this->sold;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getVolumeDonation(): ?Money
    {
        return $this->volumeDonation;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getHolders(): ?int
    {
        return $this->holders;
    }
}
