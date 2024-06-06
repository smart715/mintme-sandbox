<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TraderBalanceView
{
    private User $user;

    private string $balance;

    private ?DateTimeImmutable $date;

    private int $rank;

    public function __construct(User $user, string $balance, ?DateTimeImmutable $date)
    {
        $this->user = $user;
        $this->balance = $balance;
        $this->date = $date;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getTimestamp(): ?int
    {
        return $this->date
            ? $this->date->getTimestamp()
            : null;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getBalance(): string
    {
        return $this->balance;
    }

    /**
     * @Groups({"API", "API_BASIC"})
     */
    public function getRank(): int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }
}
