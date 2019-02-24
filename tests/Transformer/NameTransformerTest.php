<?php

namespace App\Tests\Transformer;

use App\Form\DataTransformer\NameTransformer;
use PHPUnit\Framework\TestCase;

class NameTransformerTest extends TestCase
{
    public function testRemovingMultiSpaces(): void
    {
        $nameTransformer = new NameTransformer();
        $this->assertEquals('foo bar', $nameTransformer->transform('foo     bar'));
        $this->assertEquals('foo bar', $nameTransformer->reverseTransform('foo     bar'));
    }
}
