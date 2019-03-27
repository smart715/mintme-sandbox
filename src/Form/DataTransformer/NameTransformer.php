<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use App\Utils\Converter\TokenNameConverter;
use Symfony\Component\Form\DataTransformerInterface;

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
