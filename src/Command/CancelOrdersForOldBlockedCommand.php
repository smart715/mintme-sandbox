<?php declare(strict_types = 1);

namespace App\Command;

use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CancelOrdersForOldBlockedCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'app:cancelOrders';

    private TokenManagerInterface $tokenManager;

    private UserManagerInterface $userManager;

    private MarketHandlerInterface $marketHandler;

    private MarketFactoryInterface $marketFactory;

    private CryptoManagerInterface $cryptoManager;

    private ExchangerInterface $exchanger;

    private int $maxActiveOrders;

    public function __construct(
        TokenManagerInterface $tokenManager,
        UserManagerInterface $userManager,
        MarketHandlerInterface $marketHandler,
        MarketFactoryInterface $marketFactory,
        CryptoManagerInterface $cryptoManager,
        ExchangerInterface $exchanger,
        int $maxActiveOrders
    ) {
        $this->tokenManager = $tokenManager;
        $this->userManager = $userManager;
        $this->marketHandler = $marketHandler;
        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
        $this->exchanger = $exchanger;
        $this->maxActiveOrders = $maxActiveOrders;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('cancel orders for old already tokens and user blocked');
    }

    /** @inheritDoc */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->userManager->getBlockedUsers() as $user) {
            $coinMarkets = $this->marketFactory->getCoinMarkets();
            $userPendingOrders = $this->marketHandler->getPendingOrdersByUser(
                $user,
                $coinMarkets,
                0,
                $this->maxActiveOrders
            );

            foreach ($userPendingOrders as $order) {
                $market = $order->getMarket();
                $this->exchanger->cancelOrder($market, $order);
            }
        }

        foreach ($this->tokenManager->getBlockedTokens() as $token) {
            $tokenMarket = $this->marketFactory->create(
                $this->cryptoManager->findBySymbol($token->getCryptoSymbol()),
                $token
            );
            $tokenPendingOrders = array_merge(
                $this->marketHandler->getPendingSellOrders(
                    $tokenMarket,
                    0,
                    $this->maxActiveOrders
                ),
                $this->marketHandler->getPendingBuyOrders(
                    $tokenMarket,
                    0,
                    $this->maxActiveOrders
                )
            );

            foreach ($tokenPendingOrders as $order) {
                $market = $order->getMarket();
                $this->exchanger->cancelOrder($market, $order);
            }
        }

        $io->success('orders for old user/token blocked has been cancelled');

        return 0;
    }
}
