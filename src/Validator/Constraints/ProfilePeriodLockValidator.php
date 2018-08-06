<?php

namespace App\Validator\Constraints;

use App\Entity\Profile;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProfilePeriodLockValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint)
    {
        /** @var Profile $profile */
        $profile = $this->context->getObject();

        if (null === $profile ||
            null === $profile->getNameChangedDate() ||
            !$this->isPropertyChanged($profile, $this->context->getPropertyName(), $value) ||
            $profile->getNameChangedDate()->getTimestamp() < (new DateTime())->getTimestamp())
            return;

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ date }}', $profile->getNameChangedDate()->format('Y/m/d H:i:s'))
            ->addViolation();
    }

    private function isPropertyChanged(Profile $profile, string $propertyName, string $value): bool
    {
        $originalProfileData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($profile);

        return $value !== $originalProfileData[$propertyName];
    }
}
