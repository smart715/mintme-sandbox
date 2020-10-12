<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\ProfileManager;
use App\Utils\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProfileCreatingReminderCommand extends Command
{
    /** @var string  */
    protected static $defaultName = 'app:profile-creating-reminder';

    /** @var User */
    protected $user;

    /** @var EntityManagerInterface */
    protected $em;

    /** @var MailerInterface  */
    protected $mailer;

    /** @var ProfileManager */
    protected $profileManager;

    /** @var LockFactory */
    private $lockFactory;

    public const REMINDER_INTERVAL = [1, 2, 3, 6, 6, 6, 12, 12, 12, 12, 12, 12, 12, 12];

    public function __construct(
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ProfileManager $profileManager,
        LockFactory $lockFactory
    ) {
        $this->profileManager = $profileManager;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->lockFactory = $lockFactory;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send notification email to user which has not created profile yet')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $lock = $this->lockFactory->createLock('profile-creating-reminder');

        if (!$lock->acquire()) {
            return 0;
        }

        $profiles = $this->profileManager->findAllProfileWithEmptyDescriptionAndNotAnonymous();

        foreach ($profiles as $p) {
            if (null === $p->getNextReminderDate()) {
                $p->setNextReminderDate(new \DateTime('+1 month'));
            } else {
                if (array_key_last(self::REMINDER_INTERVAL) > $p->getNumberOfReminder()) {
                    $p->setNextReminderDate(new \DateTime($p->getNextReminderDate()
                        ->modify('+'. self::REMINDER_INTERVAL[$p->getNumberOfReminder() + 1 ] . ' months')
                        ->format('Y-m-d')));
                }
            }

            $p->setNumberOfReminder($p->getNumberOfReminder() + 1);
            $this->em->persist($p);
            $this->mailer->sendProfileFillingReminderMail($p->getUser());
        }

        $this->em->flush();
        $io = new SymfonyStyle($input, $output);
        $io->success('Done.');
        $lock->release();

        return 0;
    }
}
