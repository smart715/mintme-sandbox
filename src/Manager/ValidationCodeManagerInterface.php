<?php declare(strict_types = 1);

namespace App\Manager;

use App\Config\ValidationCodeLimitsConfig;
use App\Entity\PhoneNumber;
use App\Entity\User;
use App\Entity\ValidationCode\ValidationCodeInterface;
use App\Manager\Model\SendCodeDiffModel;
use Symfony\Component\Validator\ConstraintViolationListInterface;

interface ValidationCodeManagerInterface
{
    public function addAttempts(ValidationCodeInterface $phoneCode): ValidationCodeInterface;

    public function getCodeState(
        ValidationCodeInterface $phoneCode,
        ValidationCodeLimitsConfig $limits
    ): SendCodeDiffModel;

    public function assertCode(ValidationCodeInterface $phoneCode, ValidationCodeLimitsConfig $limits): void;

    public function updateCode(ValidationCodeInterface $phoneCode, string $newCode): ValidationCodeInterface;

    public function resetTotalAttempts(ValidationCodeInterface $phoneCode): ValidationCodeInterface;

    public function sendSmsValidationCode(ValidationCodeInterface $validationCode, User $user, string $message): array;

    public function sendMailValidationCode(
        ValidationCodeInterface $validationCode,
        User $user,
        string $subject,
        ?string $to = null
    ): array;

    public function isSendSMSEnabled(?PhoneNumber $phoneNumber): bool;

    public function initValidation(
        User $user,
        ValidationCodeInterface $validationCode,
        ValidationCodeLimitsConfig $limits,
        ?string $errorMsg
    ): ConstraintViolationListInterface;
}
