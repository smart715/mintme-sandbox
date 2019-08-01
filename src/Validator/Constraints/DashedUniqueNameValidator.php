<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DashedUniqueNameValidator extends ConstraintValidator
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
        if ($this->isExisted((string)$value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function isExisted(string $tokenName): bool
    {
        return $this->entityManager->isExisted($tokenName);
    }
}
