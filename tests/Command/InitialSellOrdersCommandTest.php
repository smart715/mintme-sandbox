<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\InitialSellOrdersCommand;
use App\Entity\Crypto;
use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\OrdersFactoryInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class InitialSellOrdersCommandTest extends KernelTestCase
{
    private array $tokenManagerParams;
    private array $ordersFactoryParams;
    private array $balanceHandlerParams;
    private array $cryptoManagerParams;
    private array $marketHandlerParams;
    private array $marketFactoryParams;
    private Application $app;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    public function testWhenOneTokenDeployed(): void
    {
        $tokensParams = [
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => true],
                ['times' => 0, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 0, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 0, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 0, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 0, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 1, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 1, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
        ];

        $that = $this;

        $this->tokenManagerParams = [
            [
                'times' => 1,
                'method' => 'findAll',
                'returnValue' => array_map(
                    function ($token) use ($that) {
                        return $that->mockConstructor(Token::class, $token);
                    },
                    $tokensParams
                ),
            ],
        ];

        $this->ordersFactoryParams = [
            ['times' => 1, 'method' => 'createInitOrders'],
        ];

        $balanceResultParams = [
            ['times' => 1, 'method' => 'getAvailable', 'returnValue' => new Money(16 * 10 ** 17, new Currency('TOK'))],
        ];

        $this->balanceHandlerParams = [
            [
                'times' => 1,
                'method' => 'balance',
                'returnValue' => $this->mockConstructor(BalanceResult::class, $balanceResultParams),
            ],
        ];

        $this->cryptoManagerParams = [
            ['method' => 'findBySymbol', 'returnValue' => $this->mockConstructor(Crypto::class, [])],
        ];

        $this->marketHandlerParams = [
            ['times' => 1, 'method' => 'getPendingOrdersByUser', 'returnValue' => []],
        ];

        $this->marketFactoryParams = [
            ['times' => 1, 'method' => 'create', 'returnValue' => $this->mockConstructor(Market::class, [])],
        ];

        $mocks = $this->constructMocks();

        $output = $this->getOutput($mocks);

        $this->assertStringContainsString("Created init orders for 1 tokens", $output);
    }

    public function testWhenCreatedInitOrdersForAllTokes(): void
    {
        $tokensParams = [
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 1, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 1, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 1, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 1, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
        ];

        $that = $this;

        $this->tokenManagerParams = [
            [
                'times' => 1,
                'method' => 'findAll',
                'returnValue' => array_map(
                    function ($token) use ($that) {
                        return $that->mockConstructor(Token::class, $token);
                    },
                    $tokensParams
                ),
            ],
        ];

        $this->ordersFactoryParams = [
            ['times' => 2, 'method' => 'createInitOrders'],
        ];

        $balanceResultParams = [
            ['times' => 2, 'method' => 'getAvailable', 'returnValue' => new Money(16 * 10 ** 17, new Currency('TOK'))],
        ];

        $this->balanceHandlerParams = [
            [
                'times' => 2,
                'method' => 'balance',
                'returnValue' => $this->mockConstructor(BalanceResult::class, $balanceResultParams),
            ],
        ];

        $this->cryptoManagerParams = [
            ['method' => 'findBySymbol', 'returnValue' => $this->mockConstructor(Crypto::class, [])],
        ];

        $this->marketHandlerParams = [
            ['times' => 2, 'method' => 'getPendingOrdersByUser', 'returnValue' => []],
        ];

        $this->marketFactoryParams = [
            ['times' => 2, 'method' => 'create', 'returnValue' => $this->mockConstructor(Market::class, [])],
        ];

        $mocks = $this->constructMocks();

        $output = $this->getOutput($mocks);

        $this->assertStringContainsString("Created init orders for 2 tokens", $output);
    }

    public function testWhenOneTokenBlocked(): void
    {
        $tokensParams = [
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => true],
                ['times' => 0, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 0, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 0, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 0, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 1, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 1, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
        ];

        $that = $this;

        $this->tokenManagerParams = [
            [
                'times' => 1,
                'method' => 'findAll',
                'returnValue' => array_map(
                    function ($token) use ($that) {
                        return $that->mockConstructor(Token::class, $token);
                    },
                    $tokensParams
                ),
            ],
        ];

        $this->ordersFactoryParams = [
            ['times' => 1, 'method' => 'createInitOrders'],
        ];

        $balanceResultParams = [
            ['times' => 1, 'method' => 'getAvailable', 'returnValue' => new Money(16 * 10 ** 17, new Currency('TOK'))],
        ];

        $this->balanceHandlerParams = [
            [
                'times' => 1,
                'method' => 'balance',
                'returnValue' => $this->mockConstructor(BalanceResult::class, $balanceResultParams),
            ],
        ];

        $this->cryptoManagerParams = [
            ['method' => 'findBySymbol', 'returnValue' => $this->mockConstructor(Crypto::class, [])],
        ];

        $this->marketHandlerParams = [
            ['times' => 1, 'method' => 'getPendingOrdersByUser', 'returnValue' => []],
        ];

        $this->marketFactoryParams = [
            ['times' => 1, 'method' => 'create', 'returnValue' => $this->mockConstructor(Market::class, [])],
        ];

        $mocks = $this->constructMocks();

        $output = $this->getOutput($mocks);

        $this->assertStringContainsString("Created init orders for 1 tokens", $output);
    }

    public function testWhenNotEnoughBalance(): void
    {
        $tokensParams = [
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 0, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 0, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
            [
                ['times' => 1, 'method' => 'isDeployed', 'returnValue' => false],
                ['times' => 1, 'method' => 'isBlocked', 'returnValue' => false],
                ['times' => 1, 'method' => 'isCreatedOnMintmeSite', 'returnValue' => true],
                ['times' => 0, 'method' => 'getProfile', 'returnValue' => $this->mockConstructor(
                    Profile::class,
                    [['times' => 0, 'method' => 'getUser', 'returnValue' => $this->mockConstructor(User::class, [])]]
                )],
                ['times' => 1, 'method' => 'getOwner', 'returnValue' => $this->mockConstructor(User::class, [])],
            ],
        ];

        $that = $this;

        $this->tokenManagerParams = [
            [
                'times' => 1,
                'method' => 'findAll',
                'returnValue' => array_map(
                    function ($token) use ($that) {
                        return $that->mockConstructor(Token::class, $token);
                    },
                    $tokensParams
                ),
            ],
        ];

        $this->ordersFactoryParams = [
            ['times' => 0, 'method' => 'createInitOrders'],
        ];

        $balanceResultParams = [
            ['times' => 2, 'method' => 'getAvailable', 'returnValue' => new Money(1, new Currency('TOK'))],
        ];

        $this->balanceHandlerParams = [
            [
                'times' => 2,
                'method' => 'balance',
                'returnValue' => $this->mockConstructor(BalanceResult::class, $balanceResultParams),
            ],
        ];

        $this->cryptoManagerParams = [
            ['method' => 'findBySymbol', 'returnValue' => $this->mockConstructor(Crypto::class, [])],
        ];

        $this->marketHandlerParams = [
            ['times' => 0, 'method' => 'getPendingOrdersByUser', 'returnValue' => []],
        ];

        $this->marketFactoryParams = [
            ['times' => 0, 'method' => 'create', 'returnValue' => $this->mockConstructor(Market::class, [])],
        ];

        $mocks = $this->constructMocks();

        $output = $this->getOutput($mocks);

        $this->assertStringContainsString("Created init orders for 0 tokens", $output);
    }

    private function getOutput(array $mocks): string
    {
        $this->app->add(new InitialSellOrdersCommand(
            $mocks['tokenManager'],
            $mocks['ordersFactory'],
            $mocks['balanceHandler'],
            new MoneyWrapper($mocks['cryptoManager']),
            $mocks['marketHandler'],
            $mocks['marketFactory'],
            $mocks['cryptoManager'],
        ));

        $command = $this->app->find('app:set-initial-orders');

        $commandTester = new CommandTester($command);

        $commandTester->execute([], ['capture_stderr_separately' => 'true']);

        return $commandTester->getDisplay();
    }

    private function mockConstructor(string $className, array $properties): object
    {
        $mock = $this->createMock($className); /** @phpstan-ignore-line */

        foreach ($properties as $property) {
            switch ($property) {
                case isset($property['method']) && isset($property['returnValue']) && isset($property['times']):
                    $mock
                        ->expects($this->exactly($property['times']))
                        ->method($property['method'])
                        ->willReturn($property['returnValue']);

                    break;
                case isset($property['method']) && isset($property['times']):
                    $mock
                        ->expects($this->exactly($property['times']))
                        ->method($property['method']);

                    break;
                case isset($property['method']) && isset($property['returnValue']):
                    $mock
                        ->method($property['method'])
                        ->willReturn($property['returnValue']);

                    break;
                case isset($property['method']):
                    $mock->method($property['method']);

                    break;
                default:
                    break;
            }
        }

        return $mock;
    }

    private function constructMocks(): array
    {
        return [
            'tokenManager' => $this->mockConstructor(TokenManagerInterface::class, $this->tokenManagerParams),
            'ordersFactory' => $this->mockConstructor(OrdersFactoryInterface::class, $this->ordersFactoryParams),
            'balanceHandler' => $this->mockConstructor(BalanceHandlerInterface::class, $this->balanceHandlerParams),
            'cryptoManager' => $this->mockConstructor(CryptoManagerInterface::class, $this->cryptoManagerParams),
            'marketHandler' => $this->mockConstructor(MarketHandlerInterface::class, $this->marketHandlerParams),
            'marketFactory' => $this->mockConstructor(MarketFactoryInterface::class, $this->marketFactoryParams),
        ];
    }
}
