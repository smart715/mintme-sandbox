<?php declare(strict_types = 1);

namespace App\DataFixtures\Factory;

use App\DataFixtures\FakerHelper;
use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;

/** @codeCoverageIgnore */
class ProfileFixturesFactory extends AbstractFixturesFactory
{

    use FakerHelper;

    /** @var UserFixturesFactory */
    private $userFixturesFactory;

    public function __construct(
        EntityManagerInterface $manager,
        UserFixturesFactory $userFixturesFactory
    ) {
        $this->userFixturesFactory = $userFixturesFactory;

        parent::__construct($manager);
    }

    public function create(): Profile
    {
        $profile = new Profile(
            $this->userFixturesFactory->create()
        );

        $profile
            ->setCity($this->getFaker()->city)
            ->setCountry($this->getFaker()->country)
            ->setDescription($this->getFaker()->sentence)
            ->setFirstName($this->getFaker()->firstName)
            ->setLastName($this->getFaker()->lastName);

        $this->manager->persist($profile);
        $this->manager->flush();

        return $profile;
    }
}
