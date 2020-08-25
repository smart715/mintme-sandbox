<?php declare(strict_types = 1);

namespace App\Command;

use App\Mailer\MailerInterface;
use App\Manager\TokenManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TokenDescriptionReminderCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'app:token-description-reminder';

    public const REMINDER_INTERVAL = [1, 2, 3, 6, 6, 6, 12, 12, 12, 12, 12, 12, 12, 12];

    /** @var EntityManagerInterface */
    protected $em;

    /** @var MailerInterface  */
    protected $mailer;

    /** @var TokenManager */
    protected $tokenManager;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, TokenManager $tm)
    {
        $this->tokenManager = $tm;
        $this->em = $em;
        $this->mailer = $mailer;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this
            ->setDescription('To send email reminder for empty description to the token owner. ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tokens = $this->tokenManager->findAllTokensWithEmptyDescription();

        foreach ($tokens as $t) {
            if (null === $t->getNextReminderDate()) {
                $t->setNextReminderDate(new \DateTime('+1 month'));
            } else {
                if (array_key_last(self::REMINDER_INTERVAL) > $t->getNumberOfReminder()) {
                    $t->setNextReminderDate(new \DateTime($t->getNextReminderDate()
                        ->modify('+'. self::REMINDER_INTERVAL[$t->getNumberOfReminder() + 1 ] . ' months')
                        ->format('Y-m-d')));
                }
            }

            $t->setNumberOfReminder($t->getNumberOfReminder() + 1);
            $this->em->persist($t);
            $this->mailer->sendTokenDescriptionReminderMail($t->getProfile()->getUser());
        }

        $this->em->flush();
        $io = new SymfonyStyle($input, $output);
        $io->success('Done.');

        return 0;
    }
}