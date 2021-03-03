<?php declare(strict_types = 1);

namespace App\Utils;

use PragmaRX\Random\Random;

class RandomNumber implements RandomNumberInterface
{
    public const CODE_LENGTH = 6;

    public function getNumber(): int
    {
        return (int)(new Random())->numeric()->get();
    }

    public function generateVerificationCode(): string
    {
        // generate a fixed-length verification code that's zero-padded, e.g. 007828, 936504, 150222
        return sprintf(
            '%0'.self::CODE_LENGTH.'d',
            mt_rand(1, (int)str_repeat((string)9, self::CODE_LENGTH))
        );
    }
}
