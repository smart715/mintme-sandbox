<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Manager\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserEmailValidator extends ConstraintValidator
{
    /** @var mixed */
    private $user;

    /** @var UserManagerInterface */
    private $userManager;

    public function __construct(
        UserManagerInterface $userManager,
        TokenStorageInterface $token
    ) {
        /** @psalm-suppress UndefinedDocblockClass */
        $this->user = $token->getToken()->getUser();
        $this->userManager = $userManager;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        $user = $this->userManager->findUserByEmail($value ?? '');

        if (!is_null($user) && ($this->user !== $user || $value === $user->getEmail())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
