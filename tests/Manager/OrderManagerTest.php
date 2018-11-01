<?php

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\User;
use App\Exchange\Market;
use App\Exchange\Market\MarketFetcher;
use App\Exchange\Order;
use App\Manager\OrderManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;

class OrderManagerTest extends TestCase
{
    public function testGetPendingOrdersListWithoutOwner(): void
    {
        $pendingOrders = $this->getOrderManagerForPendingOrders()->getPendingOrdersList(
            $this->mockUser(1, 'firstName1', 'lastName1', ''),
            $this->mockMarket(),
            'sell'
        );

        $this->assertEquals(count($pendingOrders), 2);

        $this->assertEquals(
            [
                $pendingOrders[0]->getMakerFirstName(),
                $pendingOrders[0]->getMakerLastName(),
                $pendingOrders[0]->getMakerProfileUrl(),
                $pendingOrders[0]->getTakerFirstName(),
                $pendingOrders[0]->getTakerLastName(),
                $pendingOrders[0]->getTakerProfileUrl(),
                $pendingOrders[0]->getAmount(),
                $pendingOrders[0]->getPrice(),
                $pendingOrders[0]->getTotal(),
                $pendingOrders[0]->makerIsOwner(),
            ],
            [
                'firstName12',
                'lastName12',
                'profileUrl12',
                null,
                null,
                null,
                34.0,
                56.0,
                34.0 * 56.0,
                false,
            ]
        );

        $this->assertEquals(
            [
                $pendingOrders[1]->getMakerFirstName(),
                $pendingOrders[1]->getMakerLastName(),
                $pendingOrders[1]->getMakerProfileUrl(),
                $pendingOrders[1]->getTakerFirstName(),
                $pendingOrders[1]->getTakerLastName(),
                $pendingOrders[1]->getTakerProfileUrl(),
                $pendingOrders[1]->getAmount(),
                $pendingOrders[1]->getPrice(),
                $pendingOrders[1]->getTotal(),
                $pendingOrders[1]->makerIsOwner(),
            ],
            [
                'firstName21',
                'lastName21',
                'profileUrl21',
                null,
                null,
                null,
                43.0,
                65.0,
                43.0 * 65.0,
                false,
            ]
        );
    }

    public function testGetPendingOrdersListWithOwner(): void
    {
        $pendingOrders = $this->getOrderManagerForPendingOrders()->getPendingOrdersList(
            $this->mockUser(12, 'firstName12', 'lastName12', ''),
            $this->mockMarket(),
            'sell'
        );

        $this->assertEquals(count($pendingOrders), 2);

        $this->assertEquals(
            [
                $pendingOrders[0]->getMakerFirstName(),
                $pendingOrders[0]->getMakerLastName(),
                $pendingOrders[0]->getMakerProfileUrl(),
                $pendingOrders[0]->getTakerFirstName(),
                $pendingOrders[0]->getTakerLastName(),
                $pendingOrders[0]->getTakerProfileUrl(),
                $pendingOrders[0]->getAmount(),
                $pendingOrders[0]->getPrice(),
                $pendingOrders[0]->getTotal(),
                $pendingOrders[0]->makerIsOwner(),
            ],
            [
                'firstName12',
                'lastName12',
                'profileUrl12',
                null,
                null,
                null,
                34.0,
                56.0,
                34.0 * 56.0,
                true,
            ]
        );

        $this->assertEquals(
            [
                $pendingOrders[1]->getMakerFirstName(),
                $pendingOrders[1]->getMakerLastName(),
                $pendingOrders[1]->getMakerProfileUrl(),
                $pendingOrders[1]->getTakerFirstName(),
                $pendingOrders[1]->getTakerLastName(),
                $pendingOrders[1]->getTakerProfileUrl(),
                $pendingOrders[1]->getAmount(),
                $pendingOrders[1]->getPrice(),
                $pendingOrders[1]->getTotal(),
                $pendingOrders[1]->makerIsOwner(),
            ],
            [
                'firstName21',
                'lastName21',
                'profileUrl21',
                null,
                null,
                null,
                43.0,
                65.0,
                43.0 * 65.0,
                false,
            ]
        );
    }

    public function testGetOrdersHistory(): void
    {
        $pendingOrders = $this->getOrderManagerForOrdersHistory()->getOrdersHistory($this->mockMarket());

        $this->assertEquals(count($pendingOrders), 2);

        $this->assertEquals(
            [
                $pendingOrders[0]->getMakerFirstName(),
                $pendingOrders[0]->getMakerLastName(),
                $pendingOrders[0]->getMakerProfileUrl(),
                $pendingOrders[0]->getTakerFirstName(),
                $pendingOrders[0]->getTakerLastName(),
                $pendingOrders[0]->getTakerProfileUrl(),
                $pendingOrders[0]->getAmount(),
                $pendingOrders[0]->getPrice(),
                $pendingOrders[0]->getTotal(),
                $pendingOrders[0]->makerIsOwner(),
                $pendingOrders[0]->getSide(),
            ],
            [
                'firstName12',
                'lastName12',
                'profileUrl12',
                'firstName23',
                'lastName23',
                'profileUrl23',
                34.0,
                56.0,
                34.0 * 56.0,
                false,
                1,
            ]
        );

        $this->assertEquals(
            [
                $pendingOrders[1]->getMakerFirstName(),
                $pendingOrders[1]->getMakerLastName(),
                $pendingOrders[1]->getMakerProfileUrl(),
                $pendingOrders[1]->getTakerFirstName(),
                $pendingOrders[1]->getTakerLastName(),
                $pendingOrders[1]->getTakerProfileUrl(),
                $pendingOrders[1]->getAmount(),
                $pendingOrders[1]->getPrice(),
                $pendingOrders[1]->getTotal(),
                $pendingOrders[1]->makerIsOwner(),
                $pendingOrders[1]->getSide(),
            ],
            [
                'firstName21',
                'lastName21',
                'profileUrl21',
                'firstName32',
                'lastName32',
                'profileUrl32',
                43.0,
                65.0,
                43.0 * 65.0,
                false,
                2,
            ]
        );
    }

    private function getOrderManagerForPendingOrders(): OrderManager
    {
        $ordersData = [
            [
                'makerId' => 12,
                'takerId' => null,
                'amount' => 34,
                'price' => 56,
            ],
            [
                'makerId' => 21,
                'takerId' => null,
                'amount' => 43,
                'price' => 65,
            ],
        ];
        $users = [
            $this->mockUser(12, 'firstName12', 'lastName12', 'profileUrl12'),
            $this->mockUser(21, 'firstName21', 'lastName21', 'profileUrl21'),
        ];

        return new OrderManager(
            $this->mockMarketFetcher($ordersData),
            $this->mockUserManager($users)
        );
    }

    private function getOrderManagerForOrdersHistory(): OrderManager
    {
        $ordersData = [
            [
                'makerId' => 12,
                'takerId' => 23,
                'amount' => 34,
                'price' => 56,
                'side' => 1,
            ],
            [
                'makerId' => 21,
                'takerId' => 32,
                'amount' => 43,
                'price' => 65,
                'side' => 2,
            ],
        ];
        $users = [
            $this->mockUser(12, 'firstName12', 'lastName12', 'profileUrl12'),
            $this->mockUser(23, 'firstName23', 'lastName23', 'profileUrl23'),
            $this->mockUser(21, 'firstName21', 'lastName21', 'profileUrl21'),
            $this->mockUser(32, 'firstName32', 'lastName32', 'profileUrl32'),
        ];

        return new OrderManager(
            $this->mockMarketFetcher($ordersData),
            $this->mockUserManager($users)
        );
    }

    private function mockUser(int $userId, string $firstName, string $lastName, string $profileUrl): User
    {
        $userMock = $this->createMock(User::class);
        $userMock
            ->method('getId')
            ->willReturn($userId)
        ;
        $userMock
            ->method('getProfile')
            ->willReturn($this->mockProfile($firstName, $lastName, $profileUrl))
        ;
        return $userMock;
    }

    private function mockProfile(string $firstName, string $lastName, string $profileUrl): Profile
    {
        $profileMock = $this->createMock(Profile::class);
        $profileMock
            ->method('getFirstName')
            ->willReturn($firstName)
        ;
        $profileMock
            ->method('getLastName')
            ->willReturn($lastName)
        ;
        $profileMock
            ->method('getPageUrl')
            ->willReturn($profileUrl)
        ;
        return $profileMock;
    }

    private function mockMarket(): Market
    {
        return $this->createMock(Market::class);
    }

    private function mockMarketFetcher(array $ordersData): MarketFetcher
    {
        $marketFetcherMock = $this->createMock(MarketFetcher::class);

        $marketFetcherMock
            ->method('getPendingOrders')
            ->willReturn($this->ordersProvider($ordersData))
        ;

        $marketFetcherMock
            ->method('getExecutedOrders')
            ->willReturn($this->ordersProvider($ordersData))
        ;
        return $marketFetcherMock;
    }

    private function mockUserManager(array $users): UserManager
    {
        $userManagerMock = $this->createMock(UserManager::class);
        $userManagerMock
            ->method('findByIds')
            ->willReturn($users)
        ;
        return $userManagerMock;
    }

    private function mockOrder(
        int $makerId,
        ?int $takerId,
        string $amount,
        string $price,
        ?int $side
    ): Order {
        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getMakerId')
            ->willReturn($makerId)
        ;
        $orderMock
            ->method('getTakerId')
            ->willReturn($takerId)
        ;
        $orderMock
            ->method('getAmount')
            ->willReturn($amount)
        ;
        $orderMock
            ->method('getPrice')
            ->willReturn($price)
        ;
        $orderMock
            ->method('getSide')
            ->willReturn($side)
        ;
        return $orderMock;
    }

    private function ordersProvider(array $ordersData): array
    {
        return array_map(function (array $data) {
            return $this->mockOrder(
                $data['makerId'],
                $data['takerId'],
                $data['amount'],
                $data['price'],
                $data['side'] ?? null
            );
        }, $ordersData);
    }
}
