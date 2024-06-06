<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Config\FailedLoginConfig;
use App\Entity\AuthAttempts;
use App\Entity\User;
use App\Manager\AuthAttemptsManager;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthAttemptsManagerTest extends TestCase
{
    private array $entityManagerParams;
    private array $failedLoginConfigParams;
    private array $authAttemptsParams;

    public function testDecrementChances(): void
    {
        $this->entityManagerParams = [
            ['times' => 1,'method' => 'persist'],
            ['times' => 1, 'method' => 'flush'],
        ];

        $this->failedLoginConfigParams = [
            ['times' => 0, 'method' => 'getMaxHours'],
            ['times' => 0, 'method' => 'getMaxChances'],

        ];
       
        $this->authAttemptsParams = [
            ['times' => 0, 'method' => 'getUpdatedAt'],
            ['times' => 2, 'method' => 'getChances', 'returnValue' => 5],
        ];

        $mocks = $this->constructMocks();

        $authAttempts = $mocks['authAttempts'];

        $authAttempts
            ->expects($this->once())
            ->method('setChances')
            ->willReturnCallback(function ($n) {
                $authAttemptsParams = [
                    [
                        'times' => 1,
                        'method' => 'getChances',
                        'returnValue' => $n,
                    ],
                ];

                return $this->mockConstructor(
                    AuthAttempts::class,
                    $authAttemptsParams
                );
            });

        $userParams = [
            [
                'times' => 1,
                'method' => 'getAuthAttempts',
                'returnValue' => $authAttempts,
            ],
        ];

        /** @var User|MockObject $user */
        $user = $this->mockConstructor(User::class, $userParams);

        $authAttemptManager = new AuthAttemptsManager(
            $mocks['entityManager'],
            $mocks['failedLoginConfig']
        );
        
        $chances = $authAttemptManager->decrementChances($user);
        $this->assertEquals($chances, 4);
    }

    public function testDecrementChancesWhenNoAttempts(): void
    {
        $this->entityManagerParams = [
            ['times' => 1,'method' => 'persist'],
            ['times' => 1, 'method' => 'flush'],
        ];

        $this->failedLoginConfigParams = [
            ['times' => 0, 'method' => 'getMaxHours'],
            ['times' => 1, 'method' => 'getMaxChances', 'returnValue' => 10],
        ];

        $this->authAttemptsParams = [['times' => 0]];

        $mocks = $this->constructMocks();

        $authAttemptManager = new AuthAttemptsManager(
            $mocks['entityManager'],
            $mocks['failedLoginConfig']
        );

        $userParams = [
            ['times' => 1, 'method' => 'getAuthAttempts', 'returnValue' => null],
        ];

        /** @var User|MockObject $user */
        $user = $this->mockConstructor(User::class, $userParams);

        $chances = $authAttemptManager->decrementChances($user);
        $this->assertEquals($chances, 9);
    }

    public function testDecrementChancesWhenChanceZero(): void
    {
        $this->entityManagerParams = [
            ['times' => 1,'method' => 'persist'],
            ['times' => 1, 'method' => 'flush'],
        ];

        $this->failedLoginConfigParams = [
            ['times' => 0, 'method' => 'getMaxHours'],
            ['times' => 1, 'method' => 'getMaxChances', 'returnValue' => 10],
        ];

        $this->authAttemptsParams = [
            ['times' => 1, 'method' => 'getChances', 'returnValue' => 0],
        ];

        $mocks = $this->constructMocks();

        $authAttempts = $mocks['authAttempts'];

        $authAttempts
            ->expects($this->once())
            ->method('setChances')
            ->willReturnCallback(function ($n) {
                $authAttemptsParams = [
                    [
                        'times' => 1,
                        'method' => 'getChances',
                        'returnValue' => $n,
                    ],
                ];

                return $this->mockConstructor(
                    AuthAttempts::class,
                    $authAttemptsParams
                );
            });

        $userParams = [
            ['times' => 1, 'method' => 'getAuthAttempts', 'returnValue' => $authAttempts],
        ];

        /** @var User|MockObject $user */
        $user = $this->mockConstructor(User::class, $userParams);

        $authAttemptManager = new AuthAttemptsManager(
            $mocks['entityManager'],
            $mocks['failedLoginConfig']
        );

        $chances = $authAttemptManager->decrementChances($user);
        $this->assertEquals($chances, 9);
    }
    
    public function testInitChances(): void
    {
        $this->entityManagerParams = [
            ['times' => 1,'method' => 'persist'],
            ['times' => 1, 'method' => 'flush'],
        ];

        $this->failedLoginConfigParams = [
            ['times' => 0, 'method' => 'getMaxHours'],
            ['times' => 1, 'method' => 'getMaxChances'],

        ];

        $this->authAttemptsParams = [
            ['times' => 1, 'method' => 'setChances'],
        ];

        $mocks = $this->constructMocks();
        
        $userParams = [
            [
                'times' => 1,
                'method' => 'getAuthAttempts',
                'returnValue' => $mocks['authAttempts'],
            ],
        ];

        /** @var User|MockObject $user */
        $user = $this->mockConstructor(User::class, $userParams);

        $authAttemptManager = new AuthAttemptsManager(
            $mocks['entityManager'],
            $mocks['failedLoginConfig']
        );

        $authAttemptManager->initChances($user);
    }

    public function testCanDecrementChancesAndGetWaitedHours(): void
    {
        $this->entityManagerParams = [['times' => 0]];

        $this->failedLoginConfigParams = [
            ['times' => 2, 'method' => 'getMaxHours', 'returnValue' => 1],
        ];

        $this->authAttemptsParams = [
            [
                'times' => 1,
                'method' => 'getChances',
                'returnValue' => 0,
            ],
            [
                'times' => 2,
                'method' => 'getUpdatedAt',
                'returnValue' => (
                    new \DateTimeImmutable())
                        ->sub(new \DateInterval('PT7200S')),
            ],
        ];

        $mocks = $this->constructMocks();

        $userParams = [
            [
                'times' => 3,
                'method' => 'getAuthAttempts',
                'returnValue' => $mocks['authAttempts'],
            ],
        ];

        /** @var User|MockObject $user */
        $user = $this->mockConstructor(User::class, $userParams);

        $authAttemptManager = new AuthAttemptsManager(
            $mocks['entityManager'],
            $mocks['failedLoginConfig']
        );

        $canDecrement = $authAttemptManager->canDecrementChances($user);
        $this->assertEquals($canDecrement, true);

        $getMustWaitHours = $authAttemptManager->getMustWaitHours($user);
        $this->assertEquals($getMustWaitHours, 1);
    }

    private function constructMocks(): array
    {
        return [
            'entityManager' => $this->mockConstructor(
                EntityManagerInterface::class,
                $this->entityManagerParams
            ),
            'failedLoginConfig' => $this->mockConstructor(
                FailedLoginConfig::class,
                $this->failedLoginConfigParams
            ),
            'authAttempts' => $this->mockConstructor(
                AuthAttempts::class,
                $this->authAttemptsParams
            ),

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
}
