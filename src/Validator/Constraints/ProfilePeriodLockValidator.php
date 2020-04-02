<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Profile;
use App\Utils\DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ProfilePeriodLockValidator extends ConstraintValidator
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var DateTime */
    private $dateTime;

    public function __construct(EntityManagerInterface $entityManager, DateTime $dateTime)
    {
        $this->entityManager = $entityManager;
        $this->dateTime = $dateTime;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        /** @var Profile $profile */
        $profile = $this->context->getObject();

        if (null === $profile ||
            !$this->isPropertyChanged($profile, $value) ||
            null === $profile->getNameChangedDate() ||
            $profile->getNameChangedDate()->getTimestamp() < $this->dateTime->now()->getTimestamp()) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->setParameter('{{ date }}', $profile->getNameChangedDate()->format('Y/m/d H:i:s'))
            ->addViolation();
    }

    private function isPropertyChanged(Profile $profile, string $value): bool
    {
        $originalProfileData = $this->entityManager->getUnitOfWork()->getOriginalEntityData($profile);

        if (empty($originalProfileData)) {
            return true;
        }
        
        $isChanged = $value !== $originalProfileData[$this->context->getPropertyName()];

        if ($isChanged) {
            $profile->lockChanges();
        }

        return $isChanged;
    }
}
