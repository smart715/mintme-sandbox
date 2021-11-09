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
use Sonata\MediaBundle\Provider\Metadata;
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

    public function getProviderMetadata(): Metadata
    {
        return new Metadata(
            'Documents',
            'Add a document',
            null,
            'SonataMediaBundle',
            ['class' => 'fa fa-file-pdf-o']
        );
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

    protected function generateReferenceName(MediaInterface $media): string
    {
        return $this->urlConverter->convert($media->getName()) ??
            $this->generateMediaUniqId($media).'.'.$media->getBinaryContent()->guessExtension();
    }
}
