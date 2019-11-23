<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Manager\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeCanonicalEmailsCommand extends Command
{
    /** @var mixed */
    protected $gmailDomains = ['gmail.com', 'googlemail.com'];

    /** @var UserManagerInterface */
    public $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:canonicalEmail:update')
            ->setDescription('Update user canonical emails')
            ->setHelp('This command updating user canonical emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Start Command');
        $users = $this->getGmailUsers();
        $output->writeln('Received '.count($users).' users with gmail domain');
        $changeCount = 0;

        foreach ($users as $user) {
            if ($this->checkCanonicalizedEmail($this->canonicalize($user->getEmail()))) {
                 $this->userManager->updateUser($user)->getEmailCanonical();
                 $changeCount++;
            }
        }

        $output->writeln('Updated '.$changeCount.' accounts');
    }

    /** @return array */
    private function getGmailUsers(): array
    {
        $qr = $this->userManager->getRepository()->createQueryBuilder('qr');
        $merge = [];
        $qr->select('u')->from(User::class, 'u');

        foreach ($this->gmailDomains as $domain) {
            $merge = array_merge(
                $qr->Where("u.email LIKE '%@".$domain."'")
                    ->getQuery()
                    ->execute(),
                $merge
            );
        }

        return $merge;
    }

    /**
     * @param $email string
     * @return string
     */
    private function canonicalize(string $email): string
    {
        $name = strstr($email, '@', true);
        $name = str_replace('.', '', strval($name));

        return $name.'@'.$this->gmailDomains[1];
    }

    /**
     * @param $email string
     * @return bool
     */
    private function checkCanonicalizedEmail(string $email): bool
    {
        $qr = $this->userManager->getRepository()->createQueryBuilder('qr');
        $qr->select('u')->from(User::class, 'u');
        $user = $qr->Where("u.emailCanonical LIKE '".$email."'")
            ->getQuery()
            ->execute();

        if (0 == count($user)) {
            return true;
        } else if(0 != count($user)) {
            return false;
        }
    }
}
