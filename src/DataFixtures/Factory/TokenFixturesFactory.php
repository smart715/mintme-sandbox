<?php declare(strict_types = 1);

namespace App\DataFixtures\Factory;

use App\DataFixtures\FakerHelper;
use App\Entity\Token\Token;
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
        $token = (new Token())
            ->setDescription($this->getFaker()->sentence)
            ->setFacebookUrl('https://facebook.com/')
            ->setName($this->getFaker()->company)
            ->setWebsiteUrl($this->getFaker()->url)
            ->setProfile($this->profileFixturesFactory->create());

        $this->manager->persist($token);
        $this->manager->flush();

        return $token;
    }
}
