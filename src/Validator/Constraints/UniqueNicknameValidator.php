<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Profile;
use App\Entity\User;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueNicknameValidator extends ConstraintValidator
{
    /** @var ProfileManagerInterface */
    private $profileManager;

    /** @var mixed */
    private $user;

    public function __construct(
        ProfileManagerInterface $profileManager,
        TokenStorageInterface $token
    ) {
        /** @psalm-suppress UndefinedDocblockClass */
        $this->user = $token->getToken()->getUser();
        $this->profileManager = $profileManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint UniqueNickname
     */
    public function validate($value, Constraint $constraint): void
    {
        /** @var Profile|null $profile */
        $profile = $this->profileManager->findByNickname((string)$value);

        if ($profile &&
            (!$this->user instanceof User || $this->user->getId() !== $profile->getUser()->getId())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
