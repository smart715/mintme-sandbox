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
        $pendingOrders = $this->getOrderManager()->getPendingOrdersList(
            $this->mockUser(1, 'firstName1', 'lastName1'),
            $this->mockMarket(),
            'sell'
        );
        $this->assertEquals(
            $pendingOrders,
            [
                [
                    'firstName' => 'firstName12',
                    'lastName' => 'lastName12',
                    'amount' => 34.0,
                    'price' => 56.0,
                    'total' => 34.0 * 56.0,
                    'isOwner' => false,
                ],
                [
                    'firstName' => 'firstName21',
                    'lastName' => 'lastName21',
                    'amount' => 43.0,
                    'price' => 65.0,
                    'total' => 43.0 * 65.0,
                    'isOwner' => false,
                ],
            ]
        );
    }

    public function testGetPendingOrdersListWithOwner(): void
    {
        $pendingOrders = $this->getOrderManager()->getPendingOrdersList(
            $this->mockUser(12, 'firstName12', 'lastName12'),
            $this->mockMarket(),
            'sell'
        );
        $this->assertEquals(
            $pendingOrders,
            [
                [
                    'firstName' => 'firstName12',
                    'lastName' => 'lastName12',
                    'amount' => 34.0,
                    'price' => 56.0,
                    'total' => 34.0 * 56.0,
                    'isOwner' => true,
                ],
                [
                    'firstName' => 'firstName21',
                    'lastName' => 'lastName21',
                    'amount' => 43.0,
                    'price' => 65.0,
                    'total' => 43.0 * 65.0,
                    'isOwner' => false,
                ],
            ]
        );
    }

    private function getOrderManager(): OrderManager
    {
        $ordersData = [
            [
                'user' => 12,
                'amount' => 34,
                'price' => 56,
            ],
            [
                'user' => 21,
                'amount' => 43,
                'price' => 65,
            ],
        ];
        $users = [
            $this->mockUser(12, 'firstName12', 'lastName12'),
            $this->mockUser(21, 'firstName21', 'lastName21'),
        ];

        return new OrderManager(
            $this->mockMarketFetcher($ordersData),
            $this->mockUserManager($users)
        );
    }

    private function mockUser(int $userId, string $firstName, string $lastName): User
    {
        $userMock = $this->createMock(User::class);
        $userMock
            ->method('getId')
            ->willReturn($userId)
        ;
        $userMock
            ->method('getProfile')
            ->willReturn($this->mockProfile($firstName, $lastName))
        ;
        return $userMock;
    }

    private function mockProfile(string $firstName, string $lastName): Profile
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
            ->willReturn($this->pendingOrdersProvider($ordersData))
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

    private function mockOrder(int $user, string $amount, string $price): Order
    {
        $orderMock = $this->createMock(Order::class);
        $orderMock
            ->method('getMakerId')
            ->willReturn($user)
        ;
        $orderMock
            ->method('getAmount')
            ->willReturn($amount)
        ;
        $orderMock
            ->method('getPrice')
            ->willReturn($price)
        ;
        return $orderMock;
    }

    private function pendingOrdersProvider(array $ordersData): array
    {
        return array_map(function (array $data) {
            return $this->mockOrder($data['user'], $data['amount'], $data['price']);
        }, $ordersData);
    }
}
