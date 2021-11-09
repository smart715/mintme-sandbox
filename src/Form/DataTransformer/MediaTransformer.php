<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Sonata\MediaBundle\Form\DataTransformer\ProviderDataTransformer;

/** @codeCoverageIgnore */
class MediaTransformer extends ProviderDataTransformer
{
    protected function getOptions(array $options): array
    {
        return array_merge([
            'provider' => false,
            'context' => false,
            'empty_on_new' => true,
            'new_on_update' => false,
        ], $options);
    }
}
