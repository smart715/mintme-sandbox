<?php declare(strict_types = 1);

namespace App\Validator\Constraints;

use App\Entity\ValidationCode\ValidationCode;
use App\Services\TranslatorService\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidationCodeLimitsValidator extends ConstraintValidator
{
    private TranslatorInterface $translator;
    private EntityManagerInterface $entityManager;
    private string $messageType;

    private const DEFAULT_MESSAGE_TYPE = 'phone_number.add.limit.action';

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager
    ) {
        $this->translator = $translator;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param $value ValidationCode
     * @param $constraint ValidationCodeLimits
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidationCodeLimits) {
            throw new UnexpectedTypeException($constraint, ValidationCodeLimits::class);
        }

        if (!$value instanceof ValidationCode) {
            return;
        }

        $this->messageType = $constraint->messageType ?? self::DEFAULT_MESSAGE_TYPE;
        $addDate = $value->getAttemptsDate();

        if ($addDate) {
            $nowDate = new \DateTimeImmutable();

            $sameDay = $nowDate->format('d-m-Y') === $addDate->format('d-m-Y');
            $sameWeek = $nowDate->format('W-Y') === $addDate->format('W-Y');
            $sameMonth = $nowDate->format('m-Y') === $addDate->format('m-Y');

            if (!$sameDay) {
                $value->setDailyAttempts(0);
            }

            if (!$sameWeek) {
                $value->setWeeklyAttempts(0);
            }

            if (!$sameMonth) {
                $value->setMonthlyAttempts(0);
            }

            if (!$sameDay || !$sameWeek || !$sameMonth) {
                $this->entityManager->persist($value);
                $this->entityManager->flush();
            }
        }

        if ($value->getDailyAttempts() >= $constraint->dailyLimit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->getLimitMessage('limit_daily'))
                ->addViolation();
        } elseif ($value->getWeeklyAttempts() >= $constraint->weeklyLimit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->getLimitMessage('limit_weekly'))
                ->addViolation();
        } elseif ($value->getMonthlyAttempts() >= $constraint->monthlyLimit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{message}}', $this->getLimitMessage('limit_monthly'))
                ->addViolation();
        }
    }

    public function getLimitMessage(string $period): string
    {
        $messageContent = $this->translator->trans($period, [
            '%action%' =>  $this->translator->trans($this->messageType),
        ]);

        return $this->translator->trans('limit_reached', [
            '%period%' => $messageContent,
        ]);
    }
}
