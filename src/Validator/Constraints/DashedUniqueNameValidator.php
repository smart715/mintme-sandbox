<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Manager\TokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DashedUniqueNameValidator extends ConstraintValidator
{
    /** @var string[] */
    private $userTokenNames;

    /** @var TokenManagerInterface */
    private $tokenManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        TokenStorageInterface $tokenStorage
    ) {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $tokenStorage->getToken()->getUser();
        $tokens = $user->getTokens();
        $this->userTokenNames = array_map(function ($token) {
            return $token->getName();
        }, $tokens);
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint DashedUniqueName
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($this->tokenManager->isExisted((string)$value) && !in_array((string)$value, $this->userTokenNames)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
