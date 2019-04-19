<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use App\Utils\Converter\TokenNameConverter;
use App\Utils\Converter\TokenNameNormalizerInterface;
use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @var TokenNameNormalizerInterface  */
    private $tokenNameNormalizer;

    public function __construct(TokenNameNormalizerInterface $tokenNameNormalizer)
    {
        $this->tokenNameNormalizer = $tokenNameNormalizer;
    }

    /** @inheritdoc */
    public function transform($value)
    {
        return $this->tokenNameNormalizer->parse($value ?? '');
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return $this->tokenNameNormalizer->parse($value ?? '');
    }
}
