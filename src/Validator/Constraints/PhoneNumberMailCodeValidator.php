<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Services\TranslatorService\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PhoneNumberMailCodeValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private User $user;

    public function __construct(TranslatorInterface $translator, TokenStorageInterface $tokenStorage)
    {
        /**
         * @var User $user
         * @psalm-suppress UndefinedDocblockClass
         */
        $user = $tokenStorage->getToken()->getUser();
        $this->user = $user;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        $code = $this->user
            ->getProfile()
            ->getPhoneNumber()
            ->getMailCode()
            ->getCode();

        if (!$code || $value !== $code) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans(
                    'phone_confirmation.invalid_code',
                    ['%code%' => 'EMAIL'],
                ))
                ->addViolation();
        }
    }
}
