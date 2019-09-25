<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\TokenManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DashedUniqueNameValidator extends ConstraintValidator
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(TokenManagerInterface $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint DashedUniqueName
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($this->tokenManager->isExisted((string)$value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
