<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BlockTokenCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:block';

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var UserManagerInterface */
    private $userManager;

    /** @var EntityManagerInterface */
    private $em;

    /** @var UserActionLogger */
    private $logger;

    /** @var MarketHandlerInterface */
    private $marketHandler;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var ExchangerInterface */
    private $exchanger;

    public function __construct(
        TokenManagerInterface $tokenManager,
        UserManagerInterface $userManager,
        EntityManagerInterface $em,
        UserActionLogger $logger,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ExchangerInterface $exchanger
    ) {
        $this->tokenManager = $tokenManager;
        $this->userManager = $userManager;
        $this->em = $em;
        $this->logger = $logger;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->exchanger = $exchanger;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Block specified token for deposit/withdraw and remove it from trading page. '.
                'To unblock token use -unblock')
            ->setHelp('Use --unblock flag to unblock token')
            ->addArgument('name', InputArgument::REQUIRED, 'Token name,
             if token name contain spaces you should place parameter in quotes')
            ->addOption('unblock', null, InputOption::VALUE_NONE, 'Use it to unblock token')
            ->addOption(
                'token',
                't',
                InputOption::VALUE_NONE,
                'Use it to block/unblock only token and don\'t block block/unblock user'
            )
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_NONE,
                'Use it to block/unblock only user and don\'t block/unblock token'
            )
        ;
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $unblock = (bool)$input->getOption('unblock');
        $tokenOption = (bool)$input->getOption('token');
        $userOption = (bool)$input->getOption('user');
        $name = $input->getArgument('name');

        if (!is_string($name)) {
            $io->error('Wrong token/email name argument');

            return 1;
        }

        $isEmailProvided = (bool)strpos($name, '@');

        /** @var User|Token|null $entityToBlock */
        $entityToBlock = $isEmailProvided
            ? $this->userManager->findUserByEmail($name)
            : $this->tokenManager->findByName($name);

        if (!$entityToBlock) {
            $io->warning(($isEmailProvided ? 'User' : 'Token'). ' doesn\'t exist');

            return 1;
        }

        if ($isEmailProvided) {
            /** @var User $user */
            $user = $entityToBlock;
            /** @var Token|null $token */
            $token = $user->getProfile()->getMintmeToken();

        } else {
            /** @var Token $token */
            $token = $entityToBlock;
            $user = $token->getProfile()->getUser();
        }

        if ($tokenOption) {
            if ($token) {
                if ($this->isExecuted($token, $user, $unblock, $io, 'token')) {
                    return 1;
                } else {
                    $token->setIsBlocked(!$unblock);
                    $this->em->persist($token);
                }
            } else {
                $io->warning('Token doesn\'t exist');

                return 1;
            }
        } elseif ($userOption) {
            if ($this->isExecuted($token, $user, $unblock, $io, 'user')) {
                return 1;
            } else {
                $user->setIsBlocked(!$unblock);
            }
        } else {
            if ($this->isExecuted($token, $user, $unblock, $io)) {
                return 1;
            } else {
                $user->setIsBlocked(!$unblock);

                if ($token) {
                    $token->setIsBlocked(!$unblock);
                    $this->em->persist($token);
                }
            }
        }

        $entityExecutedMsg = $tokenOption
            ? 'Token '.$token->getName()
            : ($userOption
                ? 'User '.$user->getUsername()
                : 'Token '.$token->getName().' and User '.$user->getUsername()
            );

        if (!$unblock) {
            $this->cancelOrders($user, $token);
        }

        $this->em->persist($user);
        $this->em->flush();


        $this->logger->info($entityExecutedMsg.' was '.($unblock ? 'unblocked' : 'blocked'));
        $io->success($entityExecutedMsg.' was '.($unblock ? 'unblocked' : 'blocked'));

        return 0;
    }

    private function isExecuted(
        ?Token $token,
        User $user,
        bool $unblock,
        SymfonyStyle $io,
        string $option = 'both'
    ): bool {
        $blockName = 'both' === $option
            ? 'User and Token'
            : ucfirst($option);

        if ($unblock &&
            (('token' === $option && !$token->isBlocked()) ||
            ('user' === $option && !$user->isBlocked()) ||
            ('both' === $option && !$user->isBlocked() && !$token->isBlocked()))
        ) {
            $io->warning($blockName.' is already unblocked');

            return true;
        }

        if (!$unblock &&
            (('token' === $option && $token->isBlocked()) ||
            ('user' === $option &&  $user->isBlocked()) ||
            ('both' === $option && $user->isBlocked() && $token->isBlocked()))
        ) {
            $io->warning($blockName.' is already blocked');

            return true;
        }

        return false;
    }

    private function cancelOrders(User $user, Token $token): void
    {
        $market = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol($token->getExchangeCryptoSymbol()),
            $token
        );

        $orders = $this->marketHandler->getPendingOrdersByUser($user, [$market]);

        foreach ($orders as $order) {
            $this->exchanger->cancelOrder($market, $order);
        }
    }
}
