<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;

interface UserTradableInterface
{
    public function getId(): int;

    public function getUser(): User;

    public function getCreated(): ?DateTimeImmutable;
}
