<?php declare(strict_types = 1);

namespace App\Tests\Transformer;

use App\Form\DataTransformer\XSSProtectionTransformer;
use PHPUnit\Framework\TestCase;

class XSSProtectionTransformerTest extends TestCase
{
    public function testXSSProtection(): void
    {
        $xssProtectionTransformer = new XSSProtectionTransformer();
        $this->assertEquals('<tag>', $xssProtectionTransformer->transform('&lt;tag&gt;'));
        $this->assertEquals('&lt;tag&gt;', $xssProtectionTransformer->reverseTransform('<tag>'));
    }
}
