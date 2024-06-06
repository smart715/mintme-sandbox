<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Repository\TokenSignupBonusCodeRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenSignupBonusCodeValidator extends ConstraintValidator
{
    private TokenSignupBonusCodeRepository $tokenSignUpBonusRepository;
    private TranslatorInterface $translator;

    public function __construct(
        TokenSignupBonusCodeRepository $tokenSignUpBonusRepository,
        TranslatorInterface $translator
    ) {
        $this->tokenSignUpBonusRepository = $tokenSignUpBonusRepository;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @param $value string|null
     * @param $constraint TokenSignupBonusCode
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TokenSignupBonusCode) {
            throw new UnexpectedTypeException($constraint, TokenSignupBonusCode::class);
        }

        if (!$value) {
            return;
        }

        $tokenSignUpBonus = $this->tokenSignUpBonusRepository->findByCode($value);

        if (!$tokenSignUpBonus) {
            return;
        }

        if (0 >= (int) $tokenSignUpBonus->getParticipants()) {
            $this->context->buildViolation($this->translator->trans($constraint->message))
                ->addViolation();
        }
    }
}
