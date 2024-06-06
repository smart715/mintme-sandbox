<?php declare(strict_types = 1);

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\XSSProtectionTransformer;
use PHPUnit\Framework\TestCase;

class XSSProtectionTransformerTest extends TestCase
{
    public function testXSSProtection(): void
    {
        $xssProtectionTransformer = new XSSProtectionTransformer();
        $this->assertEquals('<tag>', $xssProtectionTransformer->transform('&lt;tag&gt;'));
        $this->assertEquals('&lt;tag&gt;', $xssProtectionTransformer->reverseTransform('<tag>'));
        $this->assertEquals(
            '[yt]dQw4w9WgXcQ[/yt]',
            $xssProtectionTransformer->reverseTransform('[yt]https://www.youtube.com/watch?v=dQw4w9WgXcQ[/yt]')
        );
        $this->assertEquals(
            '[yt]https://www.youtube.com/watch?v=dQw4w9Wg11XcQ[/yt]',
            $xssProtectionTransformer->reverseTransform('[yt]https://www.youtube.com/watch?v=dQw4w9Wg11XcQ[/yt]')
        );
    }
}
