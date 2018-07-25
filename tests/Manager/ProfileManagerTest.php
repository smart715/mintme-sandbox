<?php

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Manager\ProfileManager;
use App\Repository\ProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use PHPUnit\Framework\TestCase;

class ProfileManagerTest extends TestCase
{
    public function testGetProfile(): void
    {
        $user = $this->createMock(UserInterface::class);
        $profile = $this->createMock(Profile::class);

        $profileRepository = $this->createMock(ProfileRepository::class);
        $profileRepository->method('findByUser')->with($user)->willReturn($profile);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturn($profileRepository);

        $profileManager = new ProfileManager($entityManager);
        $this->assertEquals($profile, $profileManager->getProfile($user));
    }
}