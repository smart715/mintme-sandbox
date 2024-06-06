<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use App\Repository\PhoneNumberRepository;
use DateTimeImmutable;
use libphonenumber\PhoneNumber as LibphonenumberPhoneNumber;

class PhoneNumberManager implements PhoneNumberManagerInterface
{
    private PhoneNumberRepository $repository;
    private BlacklistManagerInterface $blacklistManager;

    public function __construct(
        PhoneNumberRepository $repository,
        BlacklistManagerInterface $blacklistManager
    ) {
        $this->repository = $repository;
        $this->blacklistManager = $blacklistManager;
    }

    public function getPhoneNumber(Profile $profile): ?PhoneNumber
    {
        return $this->repository->findOneBy(['profile' => $profile]);
    }

    public function findByPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber
    {
        return $this->repository->findOneBy(['phoneNumber' => $phoneNumber]);
    }

    public function findVerifiedPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber
    {
        return $this->repository->findOneBy(['phoneNumber' => $phoneNumber, 'verified' => true]);
    }

    /** @return PhoneNumber[] */
    public function findAllVerified(): array
    {
        return $this->repository->findBy(['verified' => true]);
    }

    public function findByCode(string $code): ?PhoneNumber
    {
        return $this->repository->findOneBy(['verificationCode' => $code]);
    }

    public function isPhoneNumberBlacklisted(LibphonenumberPhoneNumber $phoneNumber): bool
    {
        return $this->blacklistManager->isBlackListedNumber($phoneNumber);
    }
}
