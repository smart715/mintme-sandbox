<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\LimitHistoryConfig;
use App\Entity\Crypto;
use App\Entity\PendingTokenWithdraw;
use App\Entity\PendingWithdraw;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Manager\PendingManager;
use App\Repository\PendingTokenWithdrawRepository;
use App\Repository\PendingWithdrawRepository;
use App\Wallet\Model\Address;
use App\Wallet\Model\Amount;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PendingManagerTest extends TestCase
{
    public function testCreatePending(): void
    {
        $entityManagerParams = [
            [
                'times' => 1,
                'method' => 'persist',
            ],
            [
                'times' => 1,
                'method' => 'flush',
            ],
        ];

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockConstructor(
            EntityManagerInterface::class,
            $entityManagerParams
        );

        /** @var PendingTokenWithdrawRepository|MockObject */
        $pendingTokenRepository = $this->mockConstructor(
            PendingTokenWithdrawRepository::class,
            []
        );

        /** @var PendingWithdrawRepository|MockObject */
        $pendingCryptoRepository = $this->mockConstructor(
            PendingWithdrawRepository::class,
            []
        );

        $pendingManager = new PendingManager(
            $entityManager,
            $pendingTokenRepository,
            $pendingCryptoRepository,
            $this->mockLimitHistoryConfig()
        );

        /** @var User|MockObject */
        $user = $this->mockConstructor(User::class, []);

        /** @var Address|MockObject */
        $address = $this->mockConstructor(
            Address::class,
            ['method' => 'getAddress', 'returnValue' => 'address123']
        );

        /** @var Amount|MockObject */
        $amount = $this->mockConstructor(
            Amount::class,
            [['method' => 'getAmount', 'returnValue' => Money::USD(50)]]
        );

        /** @var Token|MockObject */
        $tradable = $this->mockConstructor(Token::class, []);

        /** @var Crypto|MockObject */
        $crypto = $this->mockConstructor(Crypto::class, []);
        
        /** @var Money */
        $fee = $this->getMoney(10);

        $expected = new PendingTokenWithdraw(
            $user,
            $tradable,
            $crypto,
            $amount,
            $address,
            $fee
        );

        $result = $pendingManager->create(
            $user,
            $address,
            $amount,
            $tradable,
            $fee,
            $crypto
        );

        $this->assertEquals($expected, $result);
    }

    public function testPendingTokenWithdraw(): void
    {
        $fee = 10;
        $amount = 50;

        $pendingTokenParams = $this->getPendingTokenParams($amount, $fee);
        
        $pendingTokenWithdraw = $this->mockConstructor(
            PendingTokenWithdraw::class,
            $pendingTokenParams,
        );

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockConstructor(
            EntityManagerInterface::class,
            []
        );

        $pendingTokenRepositoryParams = [
            [
                'method' => 'getPending',
                'returnValue' => [$pendingTokenWithdraw],
            ],
        ];

        /** @var PendingTokenWithdrawRepository|MockObject */
        $pendingTokenRepository = $this->mockConstructor(
            PendingTokenWithdrawRepository::class,
            $pendingTokenRepositoryParams
        );

        /** @var PendingWithdrawRepository|MockObject */
        $pendingCryptoRepository = $this->mockConstructor(
            PendingWithdrawRepository::class,
            []
        );

        $pendingTokenManager = new PendingManager(
            $entityManager,
            $pendingTokenRepository,
            $pendingCryptoRepository,
            $this->mockLimitHistoryConfig()
        );

        /** @var User|MockObject */
        $user = $this->mockConstructor(User::class, []);

        $resultToken = $pendingTokenManager->getPendingTokenWithdraw($user, 0, 5);

        $resultToken = [
            $resultToken[0]->getFee()->getAmount(),
            $resultToken[0]->getAmount()->getAmount(),
            $resultToken[0]->getStatus()->getStatusCode(),
        ];

        $expectedResult = [$fee, $amount, "confirmation"];

        $this->assertEquals($resultToken, $expectedResult);
    }

    public function testPendingCryptoWithdraw(): void
    {
        $fee = 10;
        $amount = 50;

        $pendingCryptoParams = $this->getPendingTokenParams($amount, $fee);

        $pendingCryptoParams[0]['method'] = 'getCrypto';
        $pendingCryptoParams[0]['returnValue'] = $this->mockConstructor(
            Crypto::class,
            []
        );

        $pendingCryptoWithdraw = $this->mockConstructor(
            PendingWithdraw::class,
            $pendingCryptoParams,
        );

        $pendingCryptoRepositoryParams = [
            [
                'method' => 'getPending',
                'returnValue' => [$pendingCryptoWithdraw],
            ],
        ];

        /** @var PendingWithdrawRepository|MockObject */
        $pendingCryptoRepository = $this->mockConstructor(
            PendingWithdrawRepository::class,
            $pendingCryptoRepositoryParams
        );

        /** @var EntityManagerInterface|MockObject */
        $entityManager = $this->mockConstructor(
            EntityManagerInterface::class,
            []
        );

        /** @var PendingTokenWithdrawRepository|MockObject */
        $pendingTokenRepository = $this->mockConstructor(
            PendingTokenWithdrawRepository::class,
            []
        );

        $pendingCryptoManager = new PendingManager(
            $entityManager,
            $pendingTokenRepository,
            $pendingCryptoRepository,
            $this->mockLimitHistoryConfig()
        );

        /** @var User|MockObject */
        $user = $this->mockConstructor(User::class, []);

        $resultCrypto = $pendingCryptoManager->getPendingCryptoWithdraw($user, 0, 5);

        $resultCrypto = [
            $resultCrypto[0]->getFee()->getAmount(),
            $resultCrypto[0]->getAmount()->getAmount(),
            $resultCrypto[0]->getStatus()->getStatusCode(),
        ];

        $expectedResult = [$fee, $amount, "confirmation"];

        $this->assertEquals($resultCrypto, $expectedResult);
    }

    private function getPendingTokenParams(int $amount, int $fee): array
    {
        return [
            [
                'times' => 1,
                'method' => 'getToken',
                'returnValue' => $this->mockConstructor(
                    Token::class,
                    []
                ),
            ],
            [
                'times' => 1,
                'method' => 'getAddress',
                'returnValue' => $this->mockConstructor(
                    Address::class,
                    [
                        [
                            'method' => 'getAddress',
                            'returnValue' => 'address123',
                        ],
                    ]
                ),
            ],
            [
                'times' => 1,
                'method' => 'getAmount',
                'returnValue' => $this->mockConstructor(
                    Amount::class,
                    [
                        [
                            'method' => 'getAmount',
                            'returnValue' => $this->getMoney($amount),
                        ],
                    ]
                ),
            ],
            [
                'times' => 1,
                'method' => 'getFee',
                'returnValue' => $this->getMoney($fee),
            ],
            [
                'times' => 1,
                'method' => 'getDate',
                'returnValue' => new \DateTimeImmutable(),
            ],
        ];
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

    private function getMoney(int $amount): Money
    {
        return new Money($amount, new Currency('USD'));
    }

    private function mockLimitHistoryConfig(): LimitHistoryConfig
    {
        return $this->createMock(LimitHistoryConfig::class);
    }
}
