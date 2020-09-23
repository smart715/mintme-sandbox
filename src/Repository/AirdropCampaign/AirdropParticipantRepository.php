<?php declare(strict_types = 1);

namespace App\Repository\AirdropCampaign;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\AirdropCampaign\AirdropParticipant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AirdropParticipantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AirdropParticipant::class);
    }

    public function getParticipantByUserAndAirdrop(User $user, Airdrop $airdrop): ?AirdropParticipant
    {
        return $this->findOneBy([
            'user' => $user,
            'airdrop' => $airdrop,
        ]);
    }
}
