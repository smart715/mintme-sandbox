<?php declare(strict_types = 1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class NameTransformer implements DataTransformerInterface
{
    /** @inheritdoc */
    public function transform($value)
    {
        return $this->removeDoubledDashesAndSpaces($value);
    }

    /** @inheritdoc */
    public function reverseTransform($value)
    {
        return $this->removeDoubledDashesAndSpaces($value);
    }

    private function removeDoubledDashesAndSpaces(?string $text): ?string
    {
        if ($text) {
            $text = (string)preg_replace('/\s+/', ' ', $text);
            $text = (string)preg_replace('/-+/', '-', $text);

            return $text;
        }

        return null;
    }
}
