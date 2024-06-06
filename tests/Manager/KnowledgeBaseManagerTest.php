<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\KnowledgeBase\Category;
use App\Entity\KnowledgeBase\KnowledgeBase;
use App\Entity\KnowledgeBase\Subcategory;
use App\Manager\KnowledgeBaseManager;
use App\Repository\KnowledgeBase\KnowledgeBaseRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class KnowledgeBaseManagerTest extends TestCase
{
    /**
     * @dataProvider getAllDataProvider
     */
    public function testGetAll(array $expected, array $allKbs): void
    {
        $knowledgeBases = [];

        foreach ($allKbs as $kb) {
            $category = $this->mockCategory();
            $category
                ->expects($this->once())
                ->method('getId')
                ->willReturn($kb['categoryId']);
            
            $knowledgeBase = $this->mockKnowledgeBase();
            $knowledgeBase
                ->expects($this->once())
                ->method('getCategory')
                ->willReturn($category);

            if ($kb['subcategoryId']) {
                $subcategory = $this->mockSubcategory();
                $subcategory
                    ->expects($this->once())
                    ->method('getId')
                    ->willReturn($kb['subcategoryId']);

                $knowledgeBase
                    ->expects($this->once())
                    ->method('getSubcategory')
                    ->willReturn($subcategory);
            }

            $knowledgeBases[] = $knowledgeBase;
        }

        $knowledgeBaseRepository = $this->mockKnowledgeRepository();
        $knowledgeBaseRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($knowledgeBases);

        $manager = new KnowledgeBaseManager(
            $knowledgeBaseRepository
        );

        $result = $manager->getAll();
        $expectedKbs = $this->constructExpected($knowledgeBases, $expected);

        $this->assertEquals($result, $expectedKbs);
    }

    public function testGetByUrl(): void
    {
        $knowledgeBase = $this->mockKnowledgeBase();

        $url = 'example.com';

        $knowledgeBaseRepository = $this->mockKnowledgeRepository();
        $knowledgeBaseRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['url' => $url])
            ->willReturn($knowledgeBase);

        $manager = new KnowledgeBaseManager(
            $knowledgeBaseRepository
        );

        $result = $manager->getByUrl($url);

        $this->assertEquals($result, $knowledgeBase);
    }

    public function getAllDataProvider(): array
    {
        return [
            'Two categories and both have subcategory' => [
                'expected' => [1 => ['1key'=>0], 2 => ['2key'=>1]],
                [
                    ['categoryId' => 1, 'subcategoryId' => 1],
                    ['categoryId' => 2, 'subcategoryId' => 2],
                ],
            ],
            'Two categories but first has no subcategory' => [
                'expected' => [1 => [0 => 0], 2 => ['2key' => 1]],
                [
                    ['categoryId' => 1, 'subcategoryId' => null],
                    ['categoryId' => 2, 'subcategoryId' => 2],
                ],
            ],
            'Two categories but second has no subcategory' => [
                'expected' => [1 => ['1key' => 0], 2 => [0 => 1]],
                [
                    ['categoryId' => 1, 'subcategoryId' => 1],
                    ['categoryId' => 2, 'subcategoryId' => null],
                ],
            ],
            'Two categories and both have no subcategory' => [
                'expected' => [1 => [0 => 0], 2 => [0 => 1]],
                [
                    ['categoryId' => 1, 'subcategoryId' => null],
                    ['categoryId' => 2, 'subcategoryId' => null],
                ],
            ],
            'One category and has subcategory' => [
                'expected' => [1 => ['1key' => 0]],
                [['categoryId' => 1, 'subcategoryId' => 1]],
            ],
            'One category and has no subcategory' => [
                'expected' => [1 => [0 => 0]],
                [['categoryId' => 1, 'subcategoryId' => null]],
            ],
        ];
    }

    private function constructExpected(array $knowledgeBases, array $expected): array
    {
        $expectedKb = [];
        array_walk(
            $expected,
            function (
                $item,
                $index
            ) use (
                $knowledgeBases,
                &$expectedKb
            ): void {
                $key = array_keys($item)[0];
                $value = $knowledgeBases[$item[$key]];
                $expectedKb[$index][$key] = $key
                ?[$value]
                :$value;
            }
        );

        return $expectedKb;
    }

    /** @return Category|MockObject */
    private function mockCategory(): Category
    {
        return $this->createMock(Category::class);
    }

    /** @return Subcategory|MockObject */
    private function mockSubcategory(): Subcategory
    {
        return $this->createMock(Subcategory::class);
    }

    /** @return KnowledgeBaseRepository|MockObject */
    private function mockKnowledgeRepository(): KnowledgeBaseRepository
    {
        return $this->createMock(KnowledgeBaseRepository::class);
    }

    /** @return KnowledgeBase|MockObject */
    private function mockKnowledgeBase(): KnowledgeBase
    {
        return $this->createMock(KnowledgeBase::class);
    }
}
