<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Admin\MainDocumentsAdmin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MainDocumentValidator extends ConstraintValidator
{
    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if (MainDocumentsAdmin::PROVIDER_NAME !== $value->getProviderName()) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
