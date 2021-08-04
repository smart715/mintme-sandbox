<?php declare(strict_types = 1);

namespace App\Tests\Utils\Converter;

use App\Utils\Converter\SlugConverter;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class SlugConverterTest extends TestCase
{
    public function testConvert(): void
    {
        $slugger = new SlugConverter();
        $repository = $this->mockRepository(false);

        $slug = $slugger->convert('hi im a title', $repository);

        $this->assertEquals('hi-im-a-title', $slug);
    }

    public function testConvertWithTakenSlugs(): void
    {
        $slugger = new SlugConverter();
        $repository = $this->mockRepository(true);

        $slug = $slugger->convert('another nice test', $repository);

        $this->assertEquals('another-nice-test-3', $slug);
    }

    private function mockRepository(bool $mockMethod): EntityRepository
    {
        $repo = $this->createMock(EntityRepository::class);
        
        if ($mockMethod) {
            // true's would be "Entities", false would be "not found"
            $repo->method('findOneBy')->will($this->onConsecutiveCalls(true, true, false));
            // slug should end with "-3"
        }

        return $repo;
    }
}
