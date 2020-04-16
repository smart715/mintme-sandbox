<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PositiveAmountValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $constraint NotEmptyWithoutBbcodes
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($value->isNegative()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
