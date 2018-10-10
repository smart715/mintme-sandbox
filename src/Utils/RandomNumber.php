<?php

namespace App\Utils;

use PragmaRX\Random\Random;

class RandomNumber implements RandomNumberInterface
{
    public function getNumber(): int
    {
        return (int)(new Random())->numeric()->get();
    }
}
