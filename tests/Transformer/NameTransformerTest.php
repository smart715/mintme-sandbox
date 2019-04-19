<?php declare(strict_types = 1);

namespace App\Tests\Transformer;

use App\Form\DataTransformer\NameTransformer;
use App\Utils\Converter\TokenNameNormalizer;
use PHPUnit\Framework\TestCase;

class NameTransformerTest extends TestCase
{
    public function testRemoveDoublespaces(): void
    {
        $nameTransformer = new NameTransformer(new TokenNameNormalizer());
        $this->assertEquals('foo bar', $nameTransformer->transform('foo     bar'));
        $this->assertEquals('foo bar', $nameTransformer->reverseTransform('foo     bar'));
    }
}
