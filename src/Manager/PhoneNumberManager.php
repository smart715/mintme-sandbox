<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\EntityManagerInterface;

class PhoneNumberManager implements PhoneNumberManagerInterface
{
    private PhoneNumberRepository $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        /** @var PhoneNumberRepository */
        $objRepo = $entityManager->getRepository(PhoneNumber::class);
        $this->entityRepository = $objRepo;
    }

    public function getPhoneNumber(Profile $profile): ?PhoneNumber
    {
        return $this->entityRepository->findOneBy(['profile' => $profile]);
    }
}
