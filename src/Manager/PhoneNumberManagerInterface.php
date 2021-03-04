<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;

interface PhoneNumberManagerInterface
{
    public function getPhoneNumber(Profile $profile): ?PhoneNumber;

    public function findByPhoneNumber(\libphonenumber\PhoneNumber $phoneNumber): ?PhoneNumber;

    public function findByCode(string $code): ?PhoneNumber;

    public function updateNumberAndAddingAttempts(PhoneNumber $phoneNumber): PhoneNumber;
}
