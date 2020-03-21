<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class AirdropParticipantRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AirdropParticipant::class);
    }

    public function getParticipantByUserAndToken(User $user, Airdrop $airdrop): ?AirdropParticipant
    {
        return $this->findOneBy([
            'user' => $user,
            'airdrop' => $airdrop,
        ]);
    }
}
