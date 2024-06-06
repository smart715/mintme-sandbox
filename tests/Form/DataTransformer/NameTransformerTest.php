<?php declare(strict_types = 1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\NameTransformer;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use PHPUnit\Framework\TestCase;

class NameTransformerTest extends TestCase
{
    public function testRemoveDoublespaces(): void
    {
        $nameTransformer = new NameTransformer();
        $this->assertEquals('foo bar', $nameTransformer->transform('foo     bar'));
        $this->assertEquals('foo bar', $nameTransformer->reverseTransform('foo     bar'));
    }
}
