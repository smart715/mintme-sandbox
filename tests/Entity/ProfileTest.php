<?php

namespace App\Tests\Entity;

use App\Entity\Profile;
use App\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ProfileTest extends TestCase
{
    /** @var Profile */
    private $profile;

    public function setUp(): void
    {
        $this->profile = new Profile();
    }

    public function testGetId(): void
    {
        $this->setProperty('id', 50);

        $this->assertEquals(50, $this->profile->getId());
    }

    public function testGetFirstName(): void
    {
        $this->setProperty('firstName', 'stubFirstName');

        $this->assertEquals('stubFirstName', $this->profile->getFirstName());
    }

    public function testGetLastName(): void
    {
        $this->setProperty('lastName', 'stubLastName');

        $this->assertEquals('stubLastName', $this->profile->getLastName());
    }

    public function testGetDescription(): void
    {
        $this->assertNull($this->profile->getDescription());

        $this->setProperty('description', 'stubDescription');
        $this->assertEquals('stubDescription', $this->profile->getDescription());
    }

    public function testGetFacebookUrl(): void
    {
        $this->assertNull($this->profile->getFacebookUrl());

        $this->setProperty('facebookUrl', 'https://facebook.com/username');
        $this->assertEquals('https://facebook.com/username', $this->profile->getFacebookUrl());
    }

    public function testSetDescription(): void
    {
        $this->assertEquals($this->profile, $this->profile->setDescription('stubDescription'));
        $this->assertEquals('stubDescription', $this->getProperty('description'));
    }

    public function testSetFacebookUrl(): void
    {
        $this->assertEquals($this->profile, $this->profile->setFacebookUrl('https://facebook.com/username'));
        $this->assertEquals('https://facebook.com/username', $this->getProperty('facebookUrl'));
    }

    public function testSetFirstName(): void
    {
        $this->assertEquals($this->profile, $this->profile->setFirstName('stubFirstName'));
        $this->assertEquals('stubFirstName', $this->getProperty('firstName'));
    }

    public function testSetLastName(): void
    {
        $this->assertEquals($this->profile, $this->profile->setLastName('stubLastName'));
        $this->assertEquals('stubLastName', $this->getProperty('lastName'));
    }

    public function testSetToken(): void
    {
        $token = $this->createMock(Token::class);

        $this->assertEquals($this->profile, $this->profile->setToken($token));
        $this->assertEquals($token, $this->getProperty('token'));
    }

    /** @param mixed $value */
    private function setProperty(string $property, $value): void
    {
        $reflector = new ReflectionClass($this->profile);
        $profileProperty = $reflector->getProperty($property);
        $profileProperty->setAccessible(true);
        $profileProperty->setValue($this->profile, $value);
    }

    /** @return mixed */
    private function getProperty(string $property)
    {
        $reflector = new ReflectionClass($this->profile);
        $profileProperty = $reflector->getProperty($property);
        $profileProperty->setAccessible(true);
        return $profileProperty->getValue($this->profile);
    }
}
