<?php declare(strict_types = 1);

namespace App\Media\Provider;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\MediaBundle\Provider\FileProvider;
use Sonata\MediaBundle\Provider\MetadataInterface;

class DocumentProvider extends FileProvider
{
    public function getProviderMetadata(): MetadataInterface
    {
        return parent::getProviderMetadata();
    }

    public function buildEditForm(FormMapper $formMapper): void
    {
        parent::buildEditForm($formMapper);
    }
}
