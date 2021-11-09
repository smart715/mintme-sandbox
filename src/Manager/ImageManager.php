<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageManager implements ImageManagerInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var FilterManager */
    private $filterManager;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        FilterManager $filterManager,
        ParameterBagInterface $parameterBag
    ) {
        $this->em = $entityManager;
        $this->filterManager = $filterManager;
        $this->parameterBag = $parameterBag;
    }

    public function upload(UploadedFile $file, string $filter): Image
    {
        $ext = (string)$file->guessExtension();
        $mime = (string)$file->getMimeType();

        $fileName = $this->generateUniqName($ext);
        $filePath = $this->getFileDir() . DIRECTORY_SEPARATOR . $fileName;

        $file->move(dirname($filePath), $fileName);

        $binary = new Binary(
            \Safe\file_get_contents($filePath),
            $mime,
            $ext
        );

        $this->saveFile(
            $filePath,
            $this->filterManager->applyFilter($binary, $filter)->getContent()
        );

        $image = new Image();
        $image->setFileName($fileName);
        $this->em->persist($image);

        return $image;
    }

    protected function generateUniqName(string $ext): string
    {
        return Uuid::uuid1()->toString() . '.' . $ext;
    }

    protected function saveFile(string $filePath, string $content): void
    {
        if (false === @file_put_contents($filePath, $content)) {
            throw new \Exception('The image could not be saved');
        }
    }

    public function getFileDir(): string
    {
        return $this->parameterBag->get('kernel.project_dir') . DIRECTORY_SEPARATOR . 'public' .
            $this->parameterBag->get('images_path');
    }

    public function getWebDir(): string
    {
        return $this->parameterBag->get('images_path');
    }
}
