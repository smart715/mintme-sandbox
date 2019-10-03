<?php declare(strict_types = 1);

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

interface TradebleInterface
{
    /** @Groups({"dev"}) */
    public function getId(): ?int;

    /** @Groups({"Default", "API", "dev"}) */
    public function getName(): string;

    /** @Groups({"Default", "API", "dev"}) */
    public function getSymbol(): string;
}
