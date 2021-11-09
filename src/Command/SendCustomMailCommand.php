<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Mailer\MailerInterface;
use App\Manager\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendCustomMailCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:sendCustomMail';

    private UserManagerInterface $userManager;

    private MailerInterface $mailer;

    private string $mintmeHostFreeDays;

    private string $mintmeHostPrice;

    private string $mintmeHostPath;

    public function __construct(
        UserManagerInterface $userManager,
        MailerInterface $mailer,
        string $mintmeHostFreeDays,
        string $mintmeHostPrice,
        string $mintmeHostPath
    ) {
        $this->userManager = $userManager;
        $this->mintmeHostFreeDays = $mintmeHostFreeDays;
        $this->mintmeHostPrice = $mintmeHostPrice;
        $this->mintmeHostPath = $mintmeHostPath;
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send mail to the any user using the email contact.')
            ->addArgument('userMailAddress', InputArgument::REQUIRED, 'mail address of the user registered on mintme')
            ->addArgument('mailToSend', InputArgument::REQUIRED, 'name of custom mail to send.')
            ->addOption(
                'userMailAddress',
                'u',
                InputOption::VALUE_NONE,
                'Use it to send the mail to the user'
            )
            ->addOption(
                'mailToSend',
                'm',
                InputOption::VALUE_NONE,
                'Use it to send the mail to the user'
            )
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $userMailAddress = $input->getArgument('userMailAddress');
        $mailToSend = $input->getArgument('mailToSend');

        if (!is_string($userMailAddress) ||
            !is_string($mailToSend) ||
            !(bool)strpos($userMailAddress, '@')
        ) {
            $io->error('Wrong user address mail or mail name argument');

            return 1;
        }

        /** @var User|null $user */
        $user =  $this->userManager->findUserByEmail($userMailAddress);

        if (!$user) {
            $io->warning('the email is not registered on mintme.com');

            return 1;
        }

        try {
            $this->mailer->{'send'.$mailToSend.'Mail'}(
                $user,
                $this->mintmeHostPrice,
                $this->mintmeHostFreeDays,
                $this->mintmeHostPath
            );
        } catch (\Throwable $e) {
            $io->warning('the mail does not exist or is not available');
            
            return 1;
        }

        $io->success('the email has ben sent to '.$userMailAddress);

        return 0;
    }
}
