<?php

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return preg_replace('/\s+/', ' ', $value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return preg_replace('/\s+/', ' ', $value);
    }
}
