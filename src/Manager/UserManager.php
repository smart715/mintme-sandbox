<?php

namespace App\Manager;

use App\Entity\User;
use App\OrmAdapter\OrmAdapterInterface;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;

class UserManager implements UserManagerInterface
{
    /** @var UserRepository */
    private $userRepository;
    
    /** @var EntityManagerInterface */
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository(User::class);
    }
   
    public function createUserReferral(UserInterface $user, string $referralCode): ?User
    {
        $referrer = $this->userRepository->findOneBy([ 'referralCode' => $referralCode ]);

        if (is_null($referrer)) {
            $user->referenceBy($referrer);
        }
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
    
    
    public function getReferencesTotal(int $userId): ?int
    {
        $referencees = $this->userRepository->findBy([ 'referencerId' => $userId ]);
        if (null === $referencees)
            return 0;
        
        return count($referencees);
    }

}
