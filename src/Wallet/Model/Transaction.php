<?php declare(strict_types = 1);

namespace App\Wallet\Model;

use App\Entity\Crypto;
use App\Entity\TradableInterface;
use App\Utils\Converter\RebrandingConverter;
use Money\Currency;
use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class Transaction
{
    private \DateTimeInterface $date;

    private ?string $hash;

    private ?Crypto $blockchain;

    private ?string $from;

    private string $to;

    private Money $amount;

    private ?Money $fee;

    private ?TradableInterface $tradable;

    private Status $status;

    private Type $type;

    private bool $isBonus;

    public function __construct(
        \DateTimeInterface $date,
        ?string $hash,
        ?string $from,
        string $to,
        Money $amount,
        ?Money $fee,
        ?TradableInterface $tradable,
        Status $status,
        Type $type,
        ?bool $isBonus = false,
        ?Crypto $blockchain = null
    ) {
        $this->date = $date;
        $this->hash = $hash;
        $this->from = $from;
        $this->to = $to;
        $this->amount = $amount;
        $this->fee = $fee;
        $this->tradable = $tradable;
        $this->status = $status;
        $this->type = $type;
        $this->isBonus = $isBonus;
        $this->blockchain = $blockchain;
    }

    /** @Groups({"API", "dev"}) */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /** @Groups({"API"}) */
    public function getFromAddress(): ?string
    {
        return $this->from;
    }

    /** @Groups({"API"}) */
    public function getToAddress(): string
    {
        return $this->to;
    }

    /** @Groups({"API", "dev"}) */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /** @Groups({"API", "dev"}) */
    public function getFee(): ?Money
    {
        return $this->fee;
    }

    /** @Groups({"API", "dev"}) */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /** @Groups({"API", "dev"}) */
    public function getTradable(): ?TradableInterface
    {
        return $this->tradable;
    }

    /** @Groups({"API", "dev"}) */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /** @Groups({"API", "dev"}) */
    public function getType(): Type
    {
        return $this->type;
    }

    /** @Groups({"dev"}) */
    public function getAddress(): string
    {
        return $this->to;
    }

    /** @Groups({"API", "dev"}) */
    public function getIsBonus(): bool
    {
        return $this->isBonus;
    }

    /** @Groups({"API", "dev"}) */
    public function getFeeCurrency(): ?Currency
    {
        $feeCurrency = $this->getFee()
            ? $this->getFee()->getCurrency()
            : null;

        if ($feeCurrency) {
            $rebranding = new RebrandingConverter();
            $feeCurrency = new Currency($rebranding->convert($feeCurrency->getCode()));
        }

        return $feeCurrency;
    }

    /** @Groups({"API", "dev"}) */
    public function getBlockchain(): ?Crypto
    {
        return $this->blockchain;
    }
}
