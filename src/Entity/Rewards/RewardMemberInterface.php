<?php declare(strict_types = 1);

namespace App\Entity\Rewards;

use App\Entity\User;
use Money\Money;

interface RewardMemberInterface
{
    public function getId(): int;

    public function getUser(): User;

    public function setUser(User $user): self;

    public function getReward(): Reward;

    public function setReward(Reward $reward): self;

    public function getNote(): ?string;

    public function setNote(?string $note): self;

    public function getPrice(): Money;

    public function setPrice(Money $price): self;

    public function isConfirmationRequired(): bool;

    public function getCreatedAt(): int;

    public function setCreatedAt(): self;
}
