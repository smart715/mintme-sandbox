<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Repository\PhoneNumberRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\TodoSniff;
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

    public function updateNumberAndAttempts(PhoneNumber $phoneNumber): void
    {
        $phoneNumber->setMonthlyAttempts($phoneNumber->getWeeklyAttempts()+1);

        $dateNow = new \DateTimeImmutable();
        $oldDate = $phoneNumber->getAttemptsDate();

        $updDailyLimit = $dateNow->format('D') !== $oldDate->format('D');
        $updWeeklyLimit = $dateNow->format('W') !== $oldDate->format('W');
        $updMonthlyLimit = $dateNow->format('M') !== $oldDate->format('M');

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

        /** @TODO update attempts date depends on compare date and weeks */
        if ($updMonthlyLimit) {
            $phoneNumber->setWeeklyAttempts(1);
        } else {
            $phoneNumber->setWeeklyAttempts($phoneNumber->getWeeklyAttempts()+1);
        }

        $this->entityManager->persist($phoneNumber);
        $this->entityManager->flush();
    }
}
