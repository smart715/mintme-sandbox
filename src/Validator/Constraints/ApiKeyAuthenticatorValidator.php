<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ApiKeyAuthenticatorValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     *
     * @param $value string
     * @param $constraint ApiKeyAuthenticator
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ApiKeyAuthenticator) {
            throw new UnexpectedTypeException($constraint, ApiKeyAuthenticator::class);
        }

        if (null !== $value) {
            if (!is_string($value)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ reason }}', 'not string')
                    ->addViolation();

                return;
            }

            if ($constraint->length != strlen($value)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ reason }}', 'invalid length')
                    ->addViolation();
            }
        } elseif (!$constraint->allowNull) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ reason }}', 'can not be null')
                ->addViolation();
        }
    }
}
