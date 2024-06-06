<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Config\UserLimitsConfig;
use App\Entity\User;
use App\Manager\TFACodesManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackupCodesDownloadLimitsValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private TFACodesManagerInterface $tfaCodesManager;
    private UserLimitsConfig $userLimitsConfig;
    public function __construct(
        TranslatorInterface $translator,
        TFACodesManagerInterface $tfaCodesManager,
        UserLimitsConfig $userLimitsConfig
    ) {
        $this->translator = $translator;
        $this->tfaCodesManager = $tfaCodesManager;
        $this->userLimitsConfig = $userLimitsConfig;
    }

    /**
     * {@inheritDoc}
     *
     * @param $value User
     * @param $constraint BackupCodesDownloadLimits
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BackupCodesDownloadLimits) {
            throw new UnexpectedTypeException($constraint, BackupCodesDownloadLimits::class);
        }

        if (!$value instanceof User) {
            throw new UnexpectedTypeException($value, User::class);
        }

        $monthLimit = $this->userLimitsConfig->getMonthlyBackupCodesLimit();

        $now = new \DateTimeImmutable();

        if ($this->tfaCodesManager->isDownloadCodesLimitReached($value, $monthLimit, $now)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->translator->trans('2fa.backup_code.download.limit_month'))
                ->addViolation();
        }
    }
}
