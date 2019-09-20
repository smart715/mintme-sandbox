<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\PendingWithdrawInterface;
use App\Entity\Token\Token;
use App\Entity\TradebleInterface;
use App\Entity\User;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Doctrine\ORM\EntityManagerInterface;

class PendingManager implements PendingManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /** @param Crypto|Token $tradable */
    public function create(User $user, Address $address, Amount $amount, TradebleInterface $tradable): PendingWithdrawInterface
    {
        $pending = $tradable instanceof Token
            ? new PendingTokenWithdraw($user, $tradable, $amount, $address)
            : new PendingWithdraw($user, $tradable, $amount, $address);

        $this->em->persist($pending);
        $this->em->flush();

        return $pending;
    }
}
