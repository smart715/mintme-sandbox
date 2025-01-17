<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class ZipCodeTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return trim(mb_strtoupper($value ?? ''));
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return trim(mb_strtoupper($value ?? ''));
    }
}
