<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class TraderBalanceView
{
    /**
     * @var User
     * @Groups({"API"})
     */
    private $user;

    /**
     * @var string
     * @Groups({"API"})
     */
    private $balance;

    /**
     * @var DateTimeImmutable
     * @Groups({"API"})
     */
    private $date;

    public function __construct(User $user, string $balance, DateTimeImmutable $date)
    {
        $this->user = $user;
        $this->balance = $balance;
        $this->date = $date;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getBalance(): string
    {
        return $this->balance;
    }
}
