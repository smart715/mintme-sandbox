<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ColorTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @param int $value
     */
    public function transform($value): string
    {
        return '#'.str_pad(dechex($value), 6, '0', STR_PAD_LEFT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $value
     */
    public function reverseTransform($value): int
    {
        try {
            return (int)hexdec($value);
        } catch (\Throwable $e) {
            throw new TransformationFailedException();
        }
    }
}
