<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\PhoneNumber;
use App\Entity\Profile;

interface PhoneNumberManagerInterface
{
    public function getPhoneNumber(Profile $profile): ?PhoneNumber;

    public function updateNumberAndAttempts(PhoneNumber $phoneNumber): void;
}
