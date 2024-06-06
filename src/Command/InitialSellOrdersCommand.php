<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\OrdersFactory;
use App\Exchange\Factory\OrdersFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitialSellOrdersCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:set-initial-orders';

    private TokenManagerInterface $tokenManager;

    private OrdersFactoryInterface $ordersFactory;

    private BalanceHandlerInterface $balanceHandler;

    private MoneyWrapperInterface $moneyWrapper;

    private MarketHandlerInterface $marketHandler;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        OrdersFactoryInterface $ordersFactory,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->ordersFactory = $ordersFactory;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketHandler = $marketHandler;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Set initial orders for already exist tokens with conditions(check execute method)');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $tokens = $this->tokenManager->findAll();
        $initOrdersFactoryCount = 0;
        /** @var ConsoleSectionOutput $section */
        /** @var ConsoleOutputInterface $output */
        $section = $output->section();

        $progressBar = $this->startProgressBar($section, $tokens);

        foreach ($tokens as $token) {
            if (!$token->isDeployed() &&
                !$token->isBlocked() &&
                $token->isCreatedOnMintmeSite() &&
                $this->tokenHasEnoughAmount($token) &&
                $this->noUserActiveSellOrder($token->getProfile()->getUser(), $token)
            ) {
                $this->ordersFactory->createInitOrders(
                    $token,
                    (string)OrdersFactory::INIT_TOKEN_PRICE,
                    null,
                    (string)OrdersFactory::INIT_TOKENS_AMOUNT
                );
                $initOrdersFactoryCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->success('Processed '.count($tokens).' tokens. Created init orders for '.$initOrdersFactoryCount.' tokens.');

        return 0;
    }

    protected function startProgressBar(ConsoleSectionOutput $section, array $tokens): ProgressBar
    {
        $progressBar = new ProgressBar($section, count($tokens));
        $progressBar->start();

        return $progressBar;
    }

    private function tokenHasEnoughAmount(Token $token): bool
    {
        $balance = $this->balanceHandler->balance($token->getOwner(), $token);

        return !$balance
            ->getAvailable()
            ->lessThan(
                $this->moneyWrapper->parse((string)OrdersFactory::INIT_TOKENS_AMOUNT, Symbols::TOK)
            );
    }

    private function noUserActiveSellOrder(User $user, Token $token): bool
    {
        $market = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol(Symbols::WEB),
            $token
        );

        $sellOrders = $this->marketHandler->getPendingOrdersByUser($user, [$market]);

        return 0 === count($sellOrders);
    }
}
