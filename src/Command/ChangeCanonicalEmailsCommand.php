<?php declare(strict_types = 1);

namespace App\Command;

use App\Manager\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeCanonicalEmailsCommand extends Command
{
    /** @var mixed */
    private $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @var UserManagerInterface */
    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:canonical-email:update')
            ->setDescription('Update user canonical emails')
            ->setHelp('This command updating user canonical emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Start Command');
        $changeCount = 0;
        $users = $this->userManager->getUsersByDomains($this->gmailDomains);

        if ($users) {
            foreach ($users as $user) {
                if (!$this->userManager->getRepository()
                    ->checkExistCanonicalEmail($this->canonicalize($user->getEmail()))
                ) {
                    $this->userManager->updateUser($user);
                    $changeCount++;
                }
            }
        }

        $output->writeln('Updated '.$changeCount.' accounts');
    }

    private function canonicalize(string $email): string
    {
        $name = strstr($email, '@', true);
        $name = str_replace('.', '', strval($name));

        return $name.'@'.$this->gmailDomains[1];
    }
}
