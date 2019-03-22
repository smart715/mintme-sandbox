<?php declare(strict_types = 1);

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
        array_map(function (): void {
            $this->tokenFixturesFactory->create();
        }, range(1, 50));
    }
}
