<?php declare(strict_types = 1);

namespace App\Tests\Utils;

use App\Utils\RandomNumber;
use PHPUnit\Framework\TestCase;

class RandomNumberTest extends TestCase
{
    private RandomNumber $randomNumber;

    protected function setUp(): void
    {
        $this->randomNumber = new RandomNumber();
    }

    public function testRandomNumber(): void
    {
        $this->assertTrue(is_int($this->randomNumber->getNumber()));
    }

    public function testGenerateVerificationCode(): void
    {
        $this->assertTrue(is_string($this->randomNumber->generateVerificationCode()));
        $this->assertEquals(6, strlen($this->randomNumber->generateVerificationCode()));
    }
}
