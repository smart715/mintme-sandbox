<?php declare(strict_types = 1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testIsGoogleAuthenticatorEnabledTest(): void
    {
        $user = new User();

        $this->assertFalse($user->isGoogleAuthenticatorEnabled());
        $user->setGoogleAuthenticatorSecret('foo');
        $this->assertTrue($user->isGoogleAuthenticatorEnabled());
    }

    public function testGetGoogleAuthenticatorSecret(): void
    {
        $user = new User();

        $this->assertEmpty($user->getGoogleAuthenticatorSecret());
        $user->setGoogleAuthenticatorSecret('foo');
        $this->assertEquals('foo', $user->getGoogleAuthenticatorSecret());
        $user->setGoogleAuthenticatorSecret('');
        $this->assertEmpty($user->getGoogleAuthenticatorSecret());
    }

    public function testIsBackupCode(): void
    {
        $user = new User();

        $this->assertFalse($user->isBackupCode('foo'));
        $user->setGoogleAuthenticatorBackupCodes(['foo', 'bar']);
        $this->assertFalse($user->isBackupCode('baz'));
        $this->assertTrue($user->isBackupCode('foo'));
    }

    public function testGetGoogleAuthenticatorBackupCodes(): void
    {
        $user = new User();

        $this->assertEmpty($user->getGoogleAuthenticatorBackupCodes());
        $user->setGoogleAuthenticatorBackupCodes(['foo']);
        $this->assertEquals(['foo'], $user->getGoogleAuthenticatorBackupCodes());
    }
}
