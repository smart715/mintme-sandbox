<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\User;
use App\Logger\UserActionLogger;
use App\Manager\OrderManagerInterface;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlockAccountCommand extends Command
{
    private const DELETED_FIELD_SUFFIX = '.deleted';
    private const FIELDS_TO_RENAME = [
        'username',
        'username_canonical',
        'email',
        'email_canonical',
    ];

    /** @var string */
    protected static $defaultName = 'app:block-account';

    private OrderManagerInterface $orderManager;
    private UserManagerInterface $userManager;
    private EntityManagerInterface $em;
    private UserActionLogger $logger;

    public function __construct(
        UserManagerInterface $userManager,
        EntityManagerInterface $em,
        UserActionLogger $logger,
        OrderManagerInterface $orderManager
    ) {
        $this->orderManager = $orderManager;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Block specified account')
            ->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Wrong email argument');

            return 1;
        }

        /** @var User|null $user */
        $user = $this->userManager->findUserByEmail($email);

        if (!$user) {
            $io->warning("User $email doesn't exist");

            return 1;
        }

        $this->blockTokens($user, $io);
        $this->cancelOrders($user, $io);
        $this->blockUser($user, $io);
        $this->removeNickname($user, $io);
        $this->renameField($user, $io);
        $this->deletePhoneNumber($user, $io);
        
        $this->em->persist($user);
        $this->em->flush();

        $this->logger->info("Account of $email was blocked");
        $io->success("Account of $email was blocked");

        return 0;
    }

    private function blockTokens(User $user, SymfonyStyle $io): void
    {
        foreach ($user->getProfile()->getTokens() as $token) {
            $token->setIsBlocked(true);

            $io->success("{$token->getName()} token blocked");
        }
    }

    private function cancelOrders(User $user, SymfonyStyle $io): void
    {
        $this->orderManager->deleteOrdersByUser($user);

        $io->success('Orders cancelled');
    }

    private function blockUser(User $user, SymfonyStyle $io): void
    {
        if ($user->isBlocked()) {
            $io->warning("{$user->getEmail()}'s account is already blocked");

            return;
        }

        $user->setIsBlocked(true);
    }

    private function removeNickname(User $user, SymfonyStyle $io): void
    {
        $user->getProfile()->setNickname(null);

        $io->success("user nickname removed");
    }

    private function renameField(User $user, SymfonyStyle $io): void
    {
        $fields = self::FIELDS_TO_RENAME;

        foreach ($fields as $field) {
            $field = str_replace('_', '', ucwords($field, '_'));
            $getterMethod = 'get'. $field;
            $setterMethod = 'set'. $field;
    
            if (method_exists($user, $getterMethod) && method_exists($user, $setterMethod)) {
                $user->$setterMethod($user->$getterMethod(). self::DELETED_FIELD_SUFFIX);

                $io->success("$field field renamed");
            } else {
                $io->error("Failed to rename $field");
            }
        }
    }

    private function deletePhoneNumber(User $user, SymfonyStyle $io): void
    {
        $profile = $user->getProfile();
        $phoneNumber = $profile->getPhoneNumber();

        if ($phoneNumber) {
            $this->em->remove($phoneNumber);
            $profile->setPhoneNumber(null);
            $this->em->persist($profile);

            $io->success('User phone number deleted');
        }
    }
}
