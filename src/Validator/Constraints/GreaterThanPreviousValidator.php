<?php

namespace App\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class GreaterThanPreviousValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        if (!$this->isGreaterThanPrevious($this->context->getObject(), $value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function isGreaterThanPrevious(object $entity, string $value): bool
    {
        $originalEntityData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($entity);

        if (empty($originalEntityData)) {
            return true;
        }

        return $value >= $originalEntityData[$this->context->getPropertyName()];
    }
}
