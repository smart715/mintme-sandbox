<?php

namespace App\DataFixtures;

use App\DataFixtures\Factory\TokenFixturesFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /** @var TokenFixturesFactory */
    private $tokenFixturesFactory;

    public function __construct(TokenFixturesFactory $tokenFixturesFactory)
    {
        $this->tokenFixturesFactory = $tokenFixturesFactory;
    }

    public function load(ObjectManager $manager): void
    {
        foreach (range(1, 50) as $index) {
            $this->tokenFixturesFactory->create();
        }
    }
}
