<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DisallowedWordValidator extends ConstraintValidator
{
    private const FORBIDDEN_WORDS = ["token", "coin"];
    /**
     * {@inheritDoc}
     *
     * @param $constraint DisallowedWord
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        array_reduce(
            self::FORBIDDEN_WORDS,
            function ($carry, $item) use ($value, $constraint): void {
                if (1 === preg_match('/\b'.$item. 's?\b/', strtolower($value))) {
                    $this->context->buildViolation($constraint->message)->addViolation();
                }
            }
        );
    }
}
