<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManager extends \FOS\UserBundle\Doctrine\UserManager implements UserManagerInterface
{
    
    public function find(int $id): ?User
    {
        return $this->getRepository()->find($id);
    }
    
    public function findByReferralCode(string $referralCode): ?User
    {
        return $this->getRepository()->findByReferralCode($referralCode);
    }
    
    public function findReferences(int $userId): ?array
    {
        return $this->getRepository()->findReferences($userId);
    }

    public function getRepository(): UserRepository
    {
        return parent::getRepository();
    }
    
    public function createUserReferral(EntityManagerInterface $entityManager, int $userId, ?string $referralCode): ?User
    {
        $user = $this->find($userId);
        $referrer = $this->findByReferralCode($referralCode);
        
        if (!is_null($referrer) && $userId !== $referrer->getId()) {
            $user->referenceBy($referrer);
        }
        $user->setReferralCode($user->getReferralCode());
        $entityManager->persist($user);
        $entityManager->flush();
        return $user;
    }
    
    
    public function getReferencesTotal(int $userId): ?int
    {
        $referencees = $this->findReferences($userId);
        if (null === $referencees) {
            return 0;
        }
        
        return count($referencees);
    }
}
