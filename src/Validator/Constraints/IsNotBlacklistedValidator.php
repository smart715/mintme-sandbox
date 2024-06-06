<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\Blacklist\Blacklist;
use App\Manager\BlacklistManagerInterface;
use App\Manager\TokenManagerInterface;
use libphonenumber\PhoneNumber;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsNotBlacklistedValidator extends ConstraintValidator
{

    private BlacklistManagerInterface $blacklistManager;
    private TokenManagerInterface $tokenManager;

    public function __construct(
        BlacklistManagerInterface $blacklistManager,
        TokenManagerInterface $tokenManager
    ) {
        $this->blacklistManager = $blacklistManager;
        $this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint IsNotBlacklisted
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value) {
            return;
        }

        if (!is_string($value) && !$value instanceof PhoneNumber) {
            throw new UnexpectedTypeException($value, 'string|PhoneNumber');
        }

        if ((Blacklist::EMAIL === $constraint->type &&
            $this->blacklistManager->isBlacklistedEmail($value, $constraint->caseSensetive))
            || ((in_array($constraint->type, Blacklist::TOKEN_TYPES, true)
                && $this->blacklistManager->isBlackListedToken($value, $constraint->caseSensetive))
                && !$this->tokenManager->findByName($value))
            || ($value instanceof PhoneNumber && Blacklist::PHONE === $constraint->type
                && $this->blacklistManager->isBlackListedNumber($value))
        ) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
