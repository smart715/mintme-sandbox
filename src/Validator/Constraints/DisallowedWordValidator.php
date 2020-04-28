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
        // TODO: Implement validate() method.

        if (null === $value || '' === $value) {
            return;
        }

        foreach (self::FORBIDDEN_WORDS as $f) {
            if (preg_match('/(\w*\s'.$f.')(s+\b|\b)/', strtolower($value)) ||
                preg_match('/(^'.$f.')(s+\b|\b)/', strtolower($value))) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}
