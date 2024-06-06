<?php declare(strict_types = 1);

namespace App\Tests\Config;

use App\Config\LimitHistoryConfig;
use PHPUnit\Framework\TestCase;

class LimitHistoryConfigTest extends TestCase
{
    public function testGetFromDate(): void
    {
        $param = 12;

        $limitHistoryConfig = new LimitHistoryConfig(12);

        $this->assertEquals(
            $limitHistoryConfig->getFromDate()->format('Y-m-d'),
            (new \DateTimeImmutable('now - ' . $param . 'month'))->format('Y-m-d')
        );
    }
}
