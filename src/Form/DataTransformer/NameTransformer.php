<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use App\Utils\Converter\TokenNameConverter;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return TokenNameConverter::parse($value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return TokenNameConverter::parse($value);
    }
}
