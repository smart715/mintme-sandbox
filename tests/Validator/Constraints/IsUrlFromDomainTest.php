<?php

namespace App\Tests\Validator\Constraints;

use App\Validator\Constraints\IsUrlFromDomain;
use PHPUnit\Framework\TestCase;

class IsUrlFromDomainTest extends TestCase
{
    public function testGetDefaultOption(): void
    {
        $this->assertEquals('domain', (new IsUrlFromDomain())->getDefaultOption());
    }
}