<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

class FooTest extends TestCase
{
    public function testFoo(): void
    {
        $foo = 'foo';
        $this->assertEquals('foo', $foo);
    }
}
