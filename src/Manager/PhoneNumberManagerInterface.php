<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;
use libphonenumber\PhoneNumber as LibphonenumberPhoneNumber;

interface PhoneNumberManagerInterface
{
    public function getPhoneNumber(Profile $profile): ?PhoneNumber;

    public function findByPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber;

    public function findVerifiedPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber;

    /** @return PhoneNumber[] */
    public function findAllVerified(): array;

    public function findByCode(string $code): ?PhoneNumber;

    public function isPhoneNumberBlacklisted(LibphonenumberPhoneNumber $phoneNumber): bool;
}
