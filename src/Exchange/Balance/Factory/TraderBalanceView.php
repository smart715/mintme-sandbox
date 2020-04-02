<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TraderBalanceView
{
    /** @var User */
    private $user;

    /** @var string */
    private $balance;

    /** @var DateTimeImmutable|null */
    private $date;

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
     * @Groups({"API"})
     * @return int
     */
    public function getTimestamp(): ?int
    {
        return $this->date
            ? $this->date->getTimestamp()
            : null;
    }

    /**
     * @Groups({"API"})
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @Groups({"API"})
     * @return string
     */
    public function getBalance(): string
    {
        return $this->balance;
    }
}
