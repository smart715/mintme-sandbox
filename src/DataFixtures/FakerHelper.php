<?php

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;

trait FakerHelper
{
    protected function getFaker(): Generator
    {
        return Factory::create();
    }
}
