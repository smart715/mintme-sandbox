<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Communications\DisposableEmailCommunicatorInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UserEmailValidator extends ConstraintValidator
{
    /** @var mixed */
    public $user;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var DisposableEmailCommunicatorInterface */
    private $apiHandler;

    public function __construct(
        UserManagerInterface $userManager,
        TokenStorageInterface $token,
        DisposableEmailCommunicatorInterface $apiHandler
    ) {
        $this->user = $token->getToken()->getUser();
        $this->userManager = $userManager;
        $this->apiHandler = $apiHandler;
    }

    /** {@inheritdoc} */
    public function validate($value, Constraint $constraint): void
    {
        $user = $this->userManager->findUserByEmail($value ?? '');

        if (!is_null($user) && ($this->user !== $user || $value === $user->getEmail())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        } elseif (true === $this->apiHandler->checkDisposable($value)) {
            $this->context->buildViolation($constraint->domainMessage)->addViolation();
        }
    }
}
