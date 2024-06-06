<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\TokenInitOrder;
use App\Entity\User;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Order;
use App\Manager\OrderManager;
use App\Manager\OrderManagerInterface;
use App\Tests\Mocks\MockMoneyWrapper;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class OrderManagerTest extends TestCase
{

    use MockMoneyWrapper;

    public function testDeleteOrdersByUser(): void
    {
        $orderManager = new OrderManager(
            $this->mockMarketFactory(),
            $this->mockMarketHandler(),
            $this->mockExchanger(),
            $this->mockEntityManager(),
        );

        $orderManager->deleteOrdersByUser($this->mockUser());
    }

    public function mockOrderManager(): OrderManagerInterface
    {
        return $this->createMock(OrderManagerInterface::class);
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
    }

    private function mockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getProfile')->willReturn($this->mockProfile());

        return $user;
    }

    private function mockMarketFactory(): MarketFactoryInterface
    {
        $marketFactory = $this->createMock(MarketFactoryInterface::class);
        $marketFactory->expects($this->once())->method('createUserRelated')->willReturn([]);

        return $marketFactory;
    }

    private function mockMarketHandler(): MarketHandlerInterface
    {
        $marketHandler = $this->createMock(MarketHandlerInterface::class);
        $marketHandler->expects($this->once())->method('getPendingOrdersByUser')->willReturn([$this->mockOrder()]);

        return $marketHandler;
    }

    private function mockExchanger(): ExchangerInterface
    {
        $exchanger = $this->createMock(ExchangerInterface::class);
        $exchanger->expects($this->once())->method('cancelOrder');

        return $exchanger;
    }

    private function mockEntityManager(): EntityManagerInterface
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('getRepository')->willReturn($this->mockRepository());
        $em->expects($this->once())->method('remove');
        $em->expects($this->once())->method('flush');

        return $em;
    }

    private function mockOrder(): Order
    {
        return $this->createMock(Order::class);
    }

    private function mockProfile(): Profile
    {
        $profile = $this->createMock(Profile::class);
        $profile->expects($this->once())->method('getFirstToken')->willReturn($this->mockToken());

        return $profile;
    }

    private function mockRepository(): ObjectRepository
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())->method('findBy')->willReturn([$this->mockTokenInitOrder()]);

        return $repository;
    }

    private function mockTokenInitOrder(): TokenInitOrder
    {
        return $this->createMock(TokenInitOrder::class);
    }
}
