<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
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
    private const ORDERS_LIMIT = 100;
    private const OPTION_BOTH = 'both';
    private const OPTION_TOKEN = 'token';
    private const OPTION_USER = 'user';

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
            if (!$token) {
                $io->warning('Token doesn\'t exist');

                return 1;
            }

            if ($this->isExecuted($token, $user, $unblock, $io, self::OPTION_TOKEN)) {
                return 1;
            } else {
                $token->setIsBlocked(!$unblock);
                $this->em->persist($token);
            }
        } elseif ($userOption) {
            if ($this->isExecuted($token, $user, $unblock, $io, self::OPTION_USER)) {
                return 1;
            } else {
                $user->setIsBlocked(!$unblock);
            }
        } else {
            if ($this->isExecuted(
                $token,
                $user,
                $unblock,
                $io,
                $token ? self::OPTION_BOTH : self::OPTION_USER
            )) {
                return 1;
            } else {
                $user->setIsBlocked(!$unblock);

                if ($token) {
                    $token->setIsBlocked(!$unblock);
                    $this->em->persist($token);
                }
            }
        }

        $entityExecutedMsg = $this->generateEntityExecutedMsg($token, $userOption ? $user : null);

        if (!$unblock && $token) {
            $this->cancelOrders($token, $tokenOption, $userOption, $io);
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
        string $option
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

    private function cancelOrders(Token $token, Bool $tokenOption, Bool $userOption, SymfonyStyle $io): void
    {
        /** @var User $user */
        $user = $token->getOwner();
        $coinMarkets = $this->marketFactory->getCoinMarkets();
        $tokenMarket = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol($token->getCryptoSymbol(), true),
            $token
        );

        if ((!$userOption && $tokenOption) || (!$userOption && !$tokenOption)) {
            $this->cancelTokenOrders($tokenMarket, Order::SELL_SIDE);
            $this->cancelTokenOrders($tokenMarket, Order::BUY_SIDE);
        }

        if (($userOption && !$tokenOption) || (!$userOption && !$tokenOption)) {
            $this->cancelCoinOrders($user, $coinMarkets);
        }
    }

    public function cancelTokenOrders(Market $market, int $side): void
    {
        do {
            $orders = Order::SELL_SIDE === $side
                ? $this->marketHandler->getPendingSellOrders($market, 0, self::ORDERS_LIMIT)
                : $this->marketHandler->getPendingBuyOrders($market, 0, self::ORDERS_LIMIT);

            $this->cancelOrdersList($orders);
        } while (count($orders) >= self::ORDERS_LIMIT);
    }

    public function cancelCoinOrders(User $user, array $markets): void
    {
        do {
            $orders = $this->marketHandler->getPendingOrdersByUser(
                $user,
                $markets,
                0,
                self::ORDERS_LIMIT
            );

            $this->cancelOrdersList($orders);
        } while (count($orders) >= self::ORDERS_LIMIT);
    }

    public function cancelOrdersList(array $orders): void
    {
        foreach ($orders as $order) {
            $this->exchanger->cancelOrder($order->getMarket(), $order);
        }
    }

    private function generateEntityExecutedMsg(?Token $token, ?User $user): string
    {
        $optionsTxt = [];

        if ($token) {
            $optionsTxt[] = 'Token '.$token->getName();
        }

        if ($user) {
            $optionsTxt[] = 'User '.$user->getUsername();
        }

        return implode(' and ', $optionsTxt);
    }
}
