<?php declare(strict_types = 1);

namespace App\Media\Provider;

use App\Utils\Converter\FriendlyUrlConverterInterface;
use Gaufrette\Adapter\Local;
use Gaufrette\Filesystem;
use Sonata\MediaBundle\CDN\Server;
use Sonata\MediaBundle\Generator\GeneratorInterface;
use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\FileProvider;
use Sonata\MediaBundle\Thumbnail\ThumbnailInterface;

class DocumentProvider extends FileProvider
{
    /** @var mixed[] */
    protected $allowedExtensions;

    /** @var mixed[] */
    protected $allowedMimeTypes;

    /** @var MetadataBuilderInterface|null  */
    protected $metadata;

    /** @var FriendlyUrlConverterInterface */
    private $urlConverter;

    /**
     * @param string $name
     * @param GeneratorInterface $pathGenerator
     * @param ThumbnailInterface $thumbnail
     * @param string $path
     * @param FriendlyUrlConverterInterface $urlConverter
     * @param array $allowedExtensions
     * @param array $allowedMimeTypes
     * @param MetadataBuilderInterface|null $metadata
     */
    public function __construct(
        string $name,
        GeneratorInterface $pathGenerator,
        ThumbnailInterface $thumbnail,
        string $path,
        FriendlyUrlConverterInterface $urlConverter,
        array $allowedExtensions = [],
        array $allowedMimeTypes = [],
        ?MetadataBuilderInterface $metadata = null
    ) {
        parent::__construct(
            $name,
            new Filesystem(new Local(getcwd().$path, true)),
            new Server($path),
            $pathGenerator,
            $thumbnail
        );

        $this->urlConverter = $urlConverter;
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferenceImage(MediaInterface $media)
    {
        return $media->getProviderReference();
    }

    public function generatePath(MediaInterface $media): string
    {
        return '';
    }
    /**
     * {@inheritdoc}
     */
    public function postUpdate(MediaInterface $media)
    {
        if (!$media->getBinaryContent() instanceof \SplFileInfo) {
            return;
        }

        // Delete the current file from the FS
        $oldMedia = clone $media;
        // if no previous reference is provided, it prevents
        // Filesystem from trying to remove a directory

        if (null !== $media->getPreviousProviderReference()) {
            $oldMedia->setProviderReference($media->getPreviousProviderReference());

            $path = $this->getReferenceImage($oldMedia);

            if ($this->getFilesystem()->has($path)) {
                $this->getFilesystem()->delete($path);
            }
        }

        $this->fixBinaryContent($media);

        $this->setFileContents($media);

        $this->generateThumbnails($media);

        $media->resetBinaryContent();
    }
    
    protected function generateReferenceName(MediaInterface $media): string
    {
        return $this->urlConverter->convert($media->getName()).'.'.$media->getBinaryContent()->guessExtension();
    }
}
