<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Crypto;
use App\Entity\Profile;
use App\Manager\ProfileManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ShowProfileInfoCommand extends Command
{
    /** @var ProfileManagerInterface */
    private $profileManager;

    public function __construct(ProfileManagerInterface $profileManager)
    {
        $this->profileManager = $profileManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:profile:info')
            ->setDescription('Show profile info')
            ->setHelp('This command shows profile info')
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the profile');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $style = new SymfonyStyle($input, $output);

        /** @var string $email */
        $email = $input->getArgument('email');
        $profile = $this->profileManager->findByEmail($email);

        if (is_null($profile)) {
            $style->error("Profile of '$email' not found");
        } else {
            $style->table(
                ['Parameter', 'Info'],
                $this->buildProfileFields($profile)
            );
        }
    }

    private function buildProfileFields(Profile $profile): array
    {
        return [
            ['First name', $profile->getFirstName()],
            ['Last name', $profile->getLastName()],
            ['Email', $profile->getUserEmail()],
            ['Changes locked', $profile->isChangesLocked() ? 'Yes' : 'No'],
        ];
    }
}
