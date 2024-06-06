<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Profile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProfileNameRequiredValidator extends ConstraintValidator
{
    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        /** @var Profile $profile */
        $profile = $this->context->getObject();

        if ('' === $value || null === $value) {
            if ('firstName' === $this->context->getPropertyName() && '' !== $profile->getLastName()) {
                $this->context->buildViolation($constraint->firstNameMessage)->addViolation();
            } elseif ('lastName' === $this->context->getPropertyName() && '' !== $profile->getFirstName()) {
                $this->context->buildViolation($constraint->lastNameMessage)->addViolation();
            }
        }
    }
}
