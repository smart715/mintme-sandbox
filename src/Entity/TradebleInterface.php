<?php declare(strict_types = 1);

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

interface TradebleInterface
{
    /** @Groups({"Default", "API"}) */
    public function getName(): string;

    /** @Groups({"Default", "API"}) */
    public function getSymbol(): string;
}
