<?php

namespace App\Order\Model;

interface OrderInfoInterface
{
    public function getMakerFirstName(): ?string;

    public function getMakerLastName(): ?string;

    public function getMakerProfileUrl(): ?string;

    public function getTakerFirstName(): ?string;

    public function getTakerLastName(): ?string;

    public function getTakerProfileUrl(): ?string;

    public function getAmount(): string;

    public function getPrice(): string;

    public function getTotal(): string;

    public function makerIsOwner(): bool;

    public function getSide(): int;

    public function getTimestamp(): ?int;
}
