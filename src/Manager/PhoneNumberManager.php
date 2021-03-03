<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Repository\PhoneNumberRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PhoneNumberManager implements PhoneNumberManagerInterface
{
    private PhoneNumberRepository $entityRepository;
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBag;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        /** @var PhoneNumberRepository */
        $objRepo = $entityManager->getRepository(PhoneNumber::class);
        $this->entityRepository = $objRepo;
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
    }

    public function getPhoneNumber(Profile $profile): ?PhoneNumber
    {
        return $this->entityRepository->findOneBy(['profile' => $profile]);
    }

    public function findByPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber
    {
        return $this->entityRepository->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function findByCode(string $code): ?PhoneNumber
    {
        return $this->entityRepository->findOneBy(['verificationCode' => $code]);
    }

    public function updateNumberAndAddingAttempts(PhoneNumber $phoneNumber): PhoneNumber
    {
        $dateNow = new DateTimeImmutable();
        $oldDate = $phoneNumber->getAttemptsDate();

        $updDailyLimit = $oldDate && $dateNow->format('DMY') !== $oldDate->format('DMY');
        $updWeeklyLimit = $oldDate && $dateNow->format('WY') !== $oldDate->format('WY');
        $updMonthlyLimit = $oldDate && $dateNow->format('MY') !== $oldDate->format('MY');

        if ($updDailyLimit) {
            $phoneNumber->setDailyAttempts(1);
        } else {
            $phoneNumber->setDailyAttempts($phoneNumber->getDailyAttempts()+1);
        }

        if ($updWeeklyLimit) {
            $phoneNumber->setWeeklyAttempts(1);
        } else {
            $phoneNumber->setWeeklyAttempts($phoneNumber->getWeeklyAttempts()+1);
        }

        if ($updMonthlyLimit) {
            $phoneNumber->setMonthlyAttempts(1);
        } else {
            $phoneNumber->setMonthlyAttempts($phoneNumber->getMonthlyAttempts()+1);
        }

        $phoneNumber->setTotalAttempts($phoneNumber->getTotalAttempts()+1);

        $phoneNumber->setAttemptsDate(new DateTimeImmutable());

        return $phoneNumber;
    }
}
