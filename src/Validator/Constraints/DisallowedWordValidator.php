<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DisallowedWordValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $constraint DisallowedWord
     */
    public function validate($value, Constraint $constraint): void
    {
        // TODO: Implement validate() method.
        $forbiddenWords = ["token", "coin"];

        if (null === $value || '' === $value) {
            return;
        }

        foreach ($forbiddenWords as $f) {
            if (false !== strpos(strtolower($value), $f)) {
                $this->context->buildViolation($constraint->message)->addViolation();
            }
        }
    }
}
