<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Image;
use App\Manager\ImageManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageManagerTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $root;

    public function testUpload(): void
    {
        $this->root = vfsStream::setup();

        $fileName = 'dummy.jpg';

        vfsStream::newFile($fileName)
            ->at($this->root)
            ->withContent('dummyContent');

        /** @var ImageManager|MockObject $manager */
        $manager = $this->getMockBuilder(ImageManager::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs([
                $this->mockEntityManager(),
                $this->mockFilterManager(),
                $this->mockParameterBag(),
            ])
            ->setMethods([
                'getFileDir',
                'saveFile',
                'generateUniqName',
            ])
            ->getMock();

        $manager->expects($this->once())
            ->method('generateUniqName')
            ->with($this->equalTo('jpg'))
            ->willReturn($fileName);

        $manager->expects($this->once())
            ->method('getFileDir')
            ->willReturn($this->root->url());

        $file = $this->mockUploadedFile();

        $image = $manager->upload(
            $file,
            'avatar'
        );

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals($image->getFileName(), $fileName);
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface|MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->expects($this->once())
            ->method('persist');

        return $entityManager;
    }

    private function mockFilterManager(): FilterManager
    {
        /** @var FilterManager|MockObject $filterManager */
        $filterManager = $this->createMock(FilterManager::class);

        $filterManager->method('applyFilter')
            ->willReturnArgument(0);

        return $filterManager;
    }

    private function mockParameterBag(): ParameterBagInterface
    {
        /** @var ParameterBagInterface|MockObject $parameterBag */
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        return $parameterBag;
    }

    private function mockUploadedFile(): UploadedFile
    {
        /** @var UploadedFile|MockObject $file */
        $file = $this->createMock(UploadedFile::class);

        $file->method('guessExtension')
            ->willReturn('jpg');

        $file->method('getMimeType')
            ->willReturn('image/jpeg');

        return $file;
    }
}
