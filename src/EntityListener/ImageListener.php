<?php declare(strict_types = 1);

namespace App\EntityListener;

use App\Entity\Image;
use App\Manager\ImageManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ImageListener
{
    /** @var ImageManagerInterface */
    private $imageManager;

    /**
     * ImageListener constructor.
     *
     * @param ImageManagerInterface $imageManager
     */
    public function __construct(ImageManagerInterface $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    public function postPersist(Image $image, LifecycleEventArgs $args): void
    {
        $this->updateImageUrl($image);
    }

    public function postLoad(Image $image, LifecycleEventArgs $args): void
    {
        $this->updateImageUrl($image);
    }

    public function preRemove(Image $image, LifecycleEventArgs $args): void
    {
        @unlink($this->imageManager->getFileDir() . DIRECTORY_SEPARATOR . $image->getFileName());
    }

    private function updateImageUrl(Image $image): void
    {
        $image->setUrl($this->imageManager->getWebDir() . DIRECTORY_SEPARATOR . $image->getFileName());
    }
}
