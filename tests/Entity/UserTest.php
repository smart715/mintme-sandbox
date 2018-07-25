<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class UserTest extends TestCase
{
    /** @var User */
    private $user;

    public function setUp(): void
    {
        $this->user = new User();
    }

    public function testSetProfile(): void
    {
        $profile = $this->createMock(Profile::class);

        $this->assertEquals($this->user, $this->user->setProfile($profile));
        $this->assertEquals($profile, $this->getProperty('profile'));
    }

    public function testSetEmail(): void
    {
        $this->assertEquals($this->user, $this->user->setEmail('example@email.com'));
        $this->assertEquals('example@email.com', $this->getProperty('email'));
        $this->assertEquals('example@email.com', $this->getProperty('username'));
    }

    /** @return mixed */
    private function getProperty(string $property)
    {
        $reflector = new ReflectionClass($this->user);
        $userProperty = $reflector->getProperty($property);
        $userProperty->setAccessible(true);
        return $userProperty->getValue($this->user);
    }
}
