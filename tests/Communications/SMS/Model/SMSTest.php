<?php declare(strict_types = 1 );

namespace App\Tests\Communications\SMS\Model;

use App\Communications\SMS\Model\SMS;
use PHPUnit\Framework\TestCase;

class SMSTest extends TestCase
{
    private const FROM = "+987654321";
    private const TO = "+123456789";
    private const CONTENT = "Hello World!";
    public const COUNTRY_CODE = '212';

    private SMS $sms;

    protected function setUp(): void
    {
        $this->sms = new SMS(self::FROM, self::TO, self::CONTENT, self::COUNTRY_CODE);
    }

    public function testGetTo(): void
    {
        $this->assertEquals(self::TO, $this->sms->getTo());
    }

    public function testGetFrom(): void
    {
        $this->assertEquals(self::FROM, $this->sms->getFrom());
    }

    public function testGetContent(): void
    {
        $this->assertEquals(self::CONTENT, $this->sms->getContent());
    }

    public function testGetCountryCode(): void
    {
        $this->assertEquals(self::COUNTRY_CODE, $this->sms->getCountryCode());
    }
}
