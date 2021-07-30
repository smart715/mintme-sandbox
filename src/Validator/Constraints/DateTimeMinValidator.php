<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DateTimeMinValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $constraint DateTimeMin
     */
    public function validate($value, Constraint $constraint): void
    {
        $modify = (string)$constraint->modify;

        if ($value < (new \DateTimeImmutable())->modify($modify)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{modify}}', $modify)
                ->addViolation();
        }
    }
}
