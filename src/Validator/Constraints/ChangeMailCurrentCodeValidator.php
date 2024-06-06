<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserChangeEmailRequestRepository;
use App\Repository\UserRepository;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ChangeMailCurrentCodeValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private User $user;
    private UserChangeEmailRequestRepository $userChangeEmailRequestRepository;

    public function __construct(
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        UserChangeEmailRequestRepository $userChangeEmailRequestRepository
    ) {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $tokenStorage->getToken()->getUser();
        $this->user = $user;
        $this->translator = $translator;
        $this->userChangeEmailRequestRepository = $userChangeEmailRequestRepository;
    }

    /**
     * {@inheritDoc}
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        $emailRequest = $this->userChangeEmailRequestRepository->findLastActiveRequest($this->user);

        if (!$emailRequest ||
            !$emailRequest->getCurrentEmailCode() ||
            $value !== $emailRequest->getCurrentEmailCode()->getCode()
        ) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans(
                    'change_email.invalid_code.current_email',
                ))
                ->addViolation();
        }
    }
}
