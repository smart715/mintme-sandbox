<?php declare(strict_types = 1);

namespace App\Entity;

use Money\Money;
use Symfony\Component\Serializer\Annotation\Groups;

interface TradableInterface
{
    /** @Groups({"dev"}) */
    public function getId(): int;

    /** @Groups({"Default", "API", "dev"}) */
    public function getName(): string;

    /** @Groups({"Default", "API", "dev"}) */
    public function getSymbol(): string;

    public function getMoneySymbol(): string;

    public function getFee(): ?Money;

    /**
     * @param string $name
     * @return mixed
     */
    public function setName(string $name);

    /**
     * @param string $symbol
     * @return mixed
     */
    public function setSymbol(string $symbol);

    public function getShowSubunit(): int;

    public function getVotings(): array;

    public static function getTradableType(): string;
}
