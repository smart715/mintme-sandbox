<?php declare(strict_types = 1);

namespace App\DataFixtures;

use Faker\Factory;
use Faker\Generator;

/** @codeCoverageIgnore */
trait FakerHelper
{
    protected function getFaker(): Generator
    {
        return Factory::create();
    }
}
