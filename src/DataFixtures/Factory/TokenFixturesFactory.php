<?php

namespace App\DataFixtures\Factory;

use App\DataFixtures\FakerHelper;
use App\Entity\Token;
use Doctrine\Common\Persistence\ObjectManager;

class TokenFixturesFactory extends AbstractFixturesFactory
{

    use FakerHelper;

    /** @var ProfileFixturesFactory */
    private $profileFixturesFactory;

    public function __construct(ObjectManager $manager, ProfileFixturesFactory $profileFixturesFactory)
    {
        $this->profileFixturesFactory = $profileFixturesFactory;

        parent::__construct($manager);
    }

    public function create(): Token
    {
        $token = new Token(
            $this->profileFixturesFactory->create(),
            $this->getFaker()->address
        );
        $token
            ->setDescription($this->getFaker()->sentence)
            ->setFacebookUrl('https://facebook.com/')
            ->setName($this->getFaker()->company)
            ->setWebsiteUrl($this->getFaker()->url);

        $this->manager->persist($token);

        $this->manager->flush();

        return $token;
    }
}
