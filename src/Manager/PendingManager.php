<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Crypto;
use App\Entity\PendingWithdraw;
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

    public function create(User $user, Address $address, Amount $amount, Crypto $crypto): PendingWithdraw
    {
        $pending = new PendingWithdraw($user, $crypto, $amount, $address);

        $this->em->persist($pending);
        $this->em->flush();

        return $pending;
    }
}
