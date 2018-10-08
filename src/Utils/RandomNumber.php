<?php

namespace App\Utils;

use PragmaRX\Random\Random;

class RandomNumber implements RandomNumberInterface
{
    public function getNumber(): int
    {
        return (new Random())->numeric()->get();
    }
}