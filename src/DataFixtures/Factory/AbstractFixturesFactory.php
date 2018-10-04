<?php

namespace App\DataFixtures\Factory;

use Doctrine\Common\Persistence\ObjectManager;

abstract class AbstractFixturesFactory
{
    /** @var ObjectManager */
    protected $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /** @return mixed */
    abstract public function create();
}
