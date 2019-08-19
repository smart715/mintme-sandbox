<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class XSSProtectionTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return html_entity_decode($value ?? '');
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return htmlentities($value ?? '');
    }
}
