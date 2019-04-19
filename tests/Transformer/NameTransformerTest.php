<?php declare(strict_types = 1);

namespace App\Tests\Transformer;

use App\Form\DataTransformer\NameTransformer;
use App\Utils\Converter\TokenNameNormalizerInterface;
use PHPUnit\Framework\TestCase;

class NameTransformerTest extends TestCase
{
    public function testRemoveDoublespaces(): void
    {
        $nameTransformer = new NameTransformer($this->createMock(TokenNameNormalizerInterface::class));
        $this->assertEquals('foo bar', $nameTransformer->transform('foo     bar'));
        $this->assertEquals('foo bar', $nameTransformer->reverseTransform('foo     bar'));
    }
}
