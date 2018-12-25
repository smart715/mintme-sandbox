<?php

namespace App\DataFixtures\Factory;

use App\DataFixtures\FakerHelper;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Model\UserManagerInterface;

class UserFixturesFactory extends AbstractFixturesFactory
{

    use FakerHelper;

    private const DEFAULT_NAME = 'mintme';
    private const DEFAULT_PASSWORD = 'Mintme123';

    /** @var int */
    private static $counter = 0;

    /** @var UserManagerInterface */
    private $userManager;

    public function __construct(ObjectManager $objectManager, UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct($objectManager);
    }

    public function create(): User
    {
        /** @var User $user */
        $user = $this->userManager->createUser()
            ->setUsername($this->getFaker()->userName)
            ->setEmail(self::DEFAULT_NAME . ++self::$counter . '@mail.org')
            ->setPlainPassword(self::DEFAULT_PASSWORD)
            ->setRoles([User::ROLE_DEFAULT])
            ->setEnabled(true);

        $this->userManager->updateUser($user);

        return $user;
    }
}
