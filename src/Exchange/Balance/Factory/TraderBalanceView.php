<?php declare(strict_types = 1);

namespace App\Exchange\Balance\Factory;

use App\Entity\User;
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

    public function __construct(User $user, string $balance)
    {
        $this->user = $user;
        $this->balance = $balance;
    }

    public function setBalance(): string
    {
        return $this->balance;
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
