<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ChangeEmailCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:change-email';

    private UserManagerInterface $userManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserManagerInterface $userManager,
        EntityManagerInterface $entityManager
    ) {
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Send mail to any user using the email contact.')
            ->addArgument('oldMail', InputArgument::REQUIRED, 'Email address of the user registered on mintme')
            ->addArgument('newMail', InputArgument::REQUIRED, 'Name of custom mail to send.')
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $newMail = $input->getArgument('newMail');
        $oldMail = $input->getArgument('oldMail');

        if (!is_string($oldMail) || !filter_var($newMail, FILTER_VALIDATE_EMAIL)) {
            $io->error('Wrong user address mail or new email argument');

            return 1;
        }

        /** @var User|null $user */
        $user = $this->userManager->findUserByEmail($oldMail);

        if (!$user) {
            $io->error('The email is not registered on mintme.com');

            return 1;
        }

        try {
            $this->userManager->changeEmail($user, $newMail);

            if (!$this->userManager->verifyNewEmail($user)) {
                $io->error('Could not verify new email');

                return 1;
            }

            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $io->error('Error while changing email address ' . $e->getMessage());

            return 1;
        }

        $io->success('The email has been changed ' . $newMail);

        return 0;
    }
}
