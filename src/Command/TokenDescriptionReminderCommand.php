<?php declare(strict_types = 1);

namespace App\Command;

use App\Mailer\MailerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\LockFactory;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TokenDescriptionReminderCommand extends Command
{
    public const REMINDER_INTERVAL = [1, 2, 3, 6, 6, 6, 12, 12, 12, 12, 12, 12, 12, 12];

    private EntityManagerInterface $em;
    private MailerInterface $mailer;
    private TokenManagerInterface $tokenManager;
    private LockFactory $lockFactory;

    public function __construct(
        EntityManagerInterface $em,
        MailerInterface $mailer,
        TokenManagerInterface $tokenManager,
        LockFactory $lockFactory
    ) {
        $this->tokenManager = $tokenManager;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->lockFactory = $lockFactory;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setName('app:token-description-reminder')
            ->setDescription('To send email reminder for empty description to the token owner.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->lockFactory->createLock('token-description-reminder');

        if (!$lock->acquire()) {
            return 0;
        }

        $tokens = $this->tokenManager->findAllTokensWithEmptyDescription();

        foreach ($tokens as $t) {
            if (null === $t->getNextReminderDate()) {
                $nextReminderDate = new \DateTimeImmutable();
                $t->setNextReminderDate($nextReminderDate->modify('1 month')->setTime(0, 0));
            } else {
                if (array_key_last(self::REMINDER_INTERVAL) > $t->getNumberOfReminder()) {
                    $t->setNextReminderDate(new \DateTimeImmutable($t->getNextReminderDate()
                        ->modify('+'. self::REMINDER_INTERVAL[$t->getNumberOfReminder() + 1 ] . ' months')
                        ->format('Y-m-d')));
                }
            }

            $t->setNumberOfReminder($t->getNumberOfReminder() + 1);
            $this->em->persist($t);
            $this->mailer->sendTokenDescriptionReminderMail($t);
        }

        $this->em->flush();
        $io = new SymfonyStyle($input, $output);
        $io->success('Done.');
        $lock->release();

        return 0;
    }
}
