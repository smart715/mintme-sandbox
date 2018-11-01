<?php

namespace App\Order;

interface OrderInfoInterface
{
    public function getMakerFirstName(): ?string;

    public function getMakerLastName(): ?string;

    public function getMakerProfileUrl(): ?string;

    public function getTakerFirstName(): ?string;

    public function getTakerLastName(): ?string;

    public function getTakerProfileUrl(): ?string;

    public function getAmount(): float;

    public function getPrice(): float;

    public function getTotal(): float;

    public function makerIsOwner(): bool;

    public function getSide(): int;

    public function getTimestamp(): ?int;
}
