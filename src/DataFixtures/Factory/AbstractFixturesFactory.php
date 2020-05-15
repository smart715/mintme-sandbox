<?php declare(strict_types = 1);

namespace App\DataFixtures\Factory;

use Doctrine\ORM\EntityManagerInterface;

/** @codeCoverageIgnore */
abstract class AbstractFixturesFactory
{
    /** @var EntityManagerInterface */
    protected $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /** @return mixed */
    abstract public function create();
}
