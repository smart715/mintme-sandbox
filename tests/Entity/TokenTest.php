<?php

namespace App\Tests;

use App\Entity\Profile;
use App\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TokenTest extends TestCase
{
    /** @var Token */
    private $token;

    public function setUp(): void
    {
        $this->token = new Token($this->createMock(Profile::class), '0xstub0123456789');
    }

    public function testGetId(): void
    {
        $this->setProperty('id', 50);
        $this->assertEquals(50, $this->token->getId());
    }

    public function testGetName(): void
    {
        $this->setProperty('name', 'stubName');
        $this->assertEquals('stubName', $this->token->getName());
    }

    public function testSetName(): void
    {
        $this->assertEquals($this->token, $this->token->setName('stubName'));
        $this->assertEquals('stubName', $this->getProperty('name'));
    }

    public function testGetAddress(): void
    {
        $this->setProperty('address', 'stubAddress');
        $this->assertEquals('stubAddress', $this->token->getAddress());
    }

    public function testGetWebsiteUrl(): void
    {
        $this->assertNull($this->token->getWebsiteUrl());

        $this->setProperty('websiteUrl', 'https://website.com');
        $this->assertEquals('https://website.com', $this->token->getWebsiteUrl());
    }

    public function testSetWebsiteUrl(): void
    {
        $this->assertEquals($this->token, $this->token->setWebsiteUrl('https://website.com'));
        $this->assertEquals('https://website.com', $this->getProperty('websiteUrl'));
    }

    public function testGetFacebookUrl(): void
    {
        $this->assertNull($this->token->getFacebookUrl());

        $this->setProperty('facebookUrl', 'https://facebook.com/username');
        $this->assertEquals('https://facebook.com/username', $this->token->getFacebookUrl());
    }

    public function testSetFacebookUrl(): void
    {
        $this->assertEquals($this->token, $this->token->setFacebookUrl('https://facebook.com/username'));
        $this->assertEquals('https://facebook.com/username', $this->getProperty('facebookUrl'));
    }

    public function testGetYoutubeUrl(): void
    {
        $this->assertNull($this->token->getYoutubeUrl());

        $this->setProperty('youtubeUrl', 'https://youtube.com/username');
        $this->assertEquals('https://youtube.com/username', $this->token->getYoutubeUrl());
    }

    public function testSetYoutubeUrl(): void
    {
        $this->assertEquals($this->token, $this->token->setYoutubeUrl('https://youtube.com/username'));
        $this->assertEquals('https://youtube.com/username', $this->getProperty('youtubeUrl'));
    }

    public function testGetDescription(): void
    {
        $this->assertNull($this->token->getDescription());

        $this->setProperty('description', 'stubDescription');
        $this->assertEquals('stubDescription', $this->token->getDescription());
    }

    public function testSetDescription(): void
    {
        $this->assertEquals($this->token, $this->token->setDescription('stubDescription'));
        $this->assertEquals('stubDescription', $this->getProperty('description'));
    }

    /** @param mixed $value */
    private function setProperty(string $property, $value): void
    {
        $reflector = new ReflectionClass($this->token);
        $tokenProperty = $reflector->getProperty($property);
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($this->token, $value);
    }

    /** @return mixed */
    private function getProperty(string $property)
    {
        $reflector = new ReflectionClass($this->token);
        $tokenProperty = $reflector->getProperty($property);
        $tokenProperty->setAccessible(true);
        return $tokenProperty->getValue($this->token);
    }
}
