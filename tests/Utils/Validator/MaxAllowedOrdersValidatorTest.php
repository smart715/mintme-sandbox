<?php declare(strict_types = 1);

namespace App\Tests\Utils\Validator;

use App\Entity\User;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Utils\Validator\MaxAllowedOrdersValidator;
use PHPUnit\Framework\TestCase;

class MaxAllowedOrdersValidatorTest extends TestCase
{
    /** @dataProvider validateProvider */
    public function testValid(
        int $userPendingOrdersCount,
        int $maxAllowedOrders,
        bool $isValid,
        string $message
    ): void {
        $validator = new MaxAllowedOrdersValidator(
            $maxAllowedOrders,
            $this->mockUser(),
            $this->mockMarketHandler($userPendingOrdersCount, $maxAllowedOrders),
            $this->mockMarketFactory()
        );

        $this->assertEquals($isValid, $validator->validate());
        $this->assertEquals($message, $validator->getMessage());
    }

    public function validateProvider(): array
    {
        return [
            "invalid if user has the same pending orders count as the max allowed" => [
                'userPendingOrdersCount' => 100,
                'maxAllowedOrders' => 100,
                'isValid' => false,
                "message" => "You can have maximum of 100 active orders",
            ],
            "Invalid if user has more pending orders than the max" => [
                "userPendingOrdersCount" => 501,
                "maxAllowedOrders" => 500,
                "isValid" => false,
                "message" => "You can have maximum of 500 active orders",
            ],
            "Valid if user has less pending orders than the max" => [
                "userPendingOrdersCount" => 99,
                "maxAllowedOrders" => 100,
                "isValid" => true,
                "message" => "You can have maximum of 100 active orders",
            ],
        ];
    }

    private function mockUser(): User
    {
        $userMock = $this->createMock(User::class);
        $userMock->method('getId')->willReturn(1);

        return $userMock;
    }

    private function mockMarketHandler(
        int $userPendingOrdersCount,
        int $maxAllowedOrders
    ): MarketHandlerInterface {
        $marketHandlerMock = $this->createMock(MarketHandlerInterface::class);
        $consecutiveCalls = $this->getConsecutiveCalls($maxAllowedOrders, $userPendingOrdersCount);

        $marketHandlerMock->method('getPendingOrdersByUser')
            ->willReturnOnConsecutiveCalls(...$consecutiveCalls);

        return $marketHandlerMock;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        return $this->createMock(MarketFactoryInterface::class);
    }

    private function getConsecutiveCalls(int $maxAllowedOrders, int $userPendingOrdersCount): array
    {
        $consecutiveCalls = [];
        $leftRequests = ceil($maxAllowedOrders / 100);

        while ($userPendingOrdersCount > 100) {
            $consecutiveCalls[] = array_fill(0, 100, null);
            $userPendingOrdersCount -= 100;
            $leftRequests--;
        }

        if ($userPendingOrdersCount > 0) {
            $consecutiveCalls[] = array_fill(0, $userPendingOrdersCount, null);
            $leftRequests--;
        }

        while ($leftRequests > 0) {
            $consecutiveCalls[] = array_fill(0, 0, null);
            $leftRequests--;
        }

        return $consecutiveCalls;
    }
}
