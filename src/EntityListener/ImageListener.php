<?php declare(strict_types = 1);

namespace App\EntityListener;

use App\Entity\Image;
use App\Manager\ImageManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;

class ImageListener
{
    private ImageManagerInterface $imageManager;
    private CacheManager $imagineCacheManager;

    /**
     * ImageListener constructor.
     *
     * @param ImageManagerInterface $imageManager
     */
    public function __construct(
        ImageManagerInterface $imageManager,
        CacheManager $imagineCacheManager
    ) {
        $this->imageManager = $imageManager;
        $this->imagineCacheManager = $imagineCacheManager;
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
        $this->imagineCacheManager->remove($image->getUrl(), ['avatar_large', 'avatar_middle', 'avatar_small']);
        @unlink($this->imageManager->getFileDir() . DIRECTORY_SEPARATOR . $image->getFileName());
    }

    private function updateImageUrl(Image $image): void
    {
        $image->setUrl($this->imageManager->getWebDir() . DIRECTORY_SEPARATOR . $image->getFileName());
    }
}
