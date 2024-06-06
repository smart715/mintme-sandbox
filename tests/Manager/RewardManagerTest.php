<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Profile;
use App\Entity\Rewards\Reward;
use App\Entity\Rewards\RewardParticipant;
use App\Entity\Rewards\RewardVolunteer;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Factory\UpdateBalanceView;
use App\Exchange\Balance\Model\BalanceResult;
use App\Logger\UserActionLogger;
use App\Manager\RewardManager;
use App\Repository\RewardParticipantRepository;
use App\Repository\RewardRepository;
use App\Repository\RewardVolunteerRepository;
use App\Utils\Converter\SlugConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Exception\NotEnoughAmountException;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class RewardManagerTest extends TestCase
{
    /** @dataProvider rewardsForGetUnfinishedProvider */
    public function testGetUnfinishedRewardsByToken(array $rewards, int $expectedCount): void
    {
        $rewardRepository = $this->createMock(RewardRepository::class);
        $rewardRepository
            ->method('getActiveRewards')
            ->with($this->createToken())
            ->willReturn($rewards);
        $rewardManager = $this->createRewardManager(
            $rewardRepository,
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(EntityManagerInterface::class)
        );

        $getUnfinishedRewards = $rewardManager->getUnfinishedRewardsByToken($this->createToken());

        $this->assertSame(
            $expectedCount,
            count(array_merge($getUnfinishedRewards[Reward::TYPE_REWARD], $getUnfinishedRewards[Reward::TYPE_BOUNTY]))
        );
    }

    public function testGetBySlug(): void
    {
        $reward = (new Reward())->setSlug('foo');
        $rewardRepository = $this->createMock(RewardRepository::class);
        $rewardRepository
            ->expects($this->exactly(1))
            ->method('findOneBy')
            ->with(['slug' => 'foo', 'status' => 1])
            ->willReturn($reward);

        $rewardManager = $this->createRewardManager(
            $rewardRepository,
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardFromManager = $rewardManager->getBySlug('foo');

        $this->assertSame($reward, $rewardFromManager);
        $this->assertSame($reward->getSlug(), $rewardFromManager->getSlug());
    }

    public function testCreateRewardTypeReward(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $reward = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setToken($token);

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler->expects($this->never())->method('balance');
        $balanceHandler->expects($this->never())->method('update');

        $rewardRepository = $this->createMock(RewardRepository::class);

        $rewardManager = $this->createRewardManager(
            $rewardRepository,
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->createReward($reward);

        $this->assertEquals(new Money('0', new Currency(Symbols::TOK)), $reward->getFrozenAmount());
        $this->assertSame('foo', $reward->getSlug());
    }

    public function testCreateRewardTypeBountyWithEnoughFunds(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $reward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setToken($token);
        $reward->setFrozenAmount(
            $reward->getPrice()->multiply($reward->getQuantity())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn(new Money('100000', new Currency(Symbols::TOK)));


        $rewardRepository = $this->createMock(RewardRepository::class);

        $rewardManager = $this->createRewardManager(
            $rewardRepository,
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->createReward($reward);

        $this->assertEquals($reward->getPrice()->multiply($reward->getQuantity()), $reward->getFrozenAmount());
        $this->assertSame('foo', $reward->getSlug());
    }

    public function testCreateRewardTypeBountyWithoutEnoughFunds(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $reward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setToken($token);
        $reward->setFrozenAmount(
            $reward->getPrice()->multiply($reward->getQuantity())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn(new Money('10', new Currency(Symbols::TOK)));

        $this->assertEquals($reward->getPrice()->multiply($reward->getQuantity()), $reward->getFrozenAmount());

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $this->expectException(NotEnoughAmountException::class);
        $rewardManager->createReward($reward);
    }

    public function testAddRewardMemberWithBonusBalance(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $reward = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('100', new Currency(Symbols::TOK)))
            ->setToken($token);
        $reward->setFrozenAmount(
            $reward->getPrice()->multiply($reward->getQuantity())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);

        $mainChange = new Money('90', new Currency(Symbols::TOK));
        $bonusChange = new Money('10', new Currency(Symbols::TOK));

        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn($mainChange->add($bonusChange));

        $balanceHandler
            ->expects($this->once())
            ->method('withdrawBonus')
            ->willReturn(new UpdateBalanceView(
                $mainChange,
                $bonusChange,
            ));

        $participant = (new RewardParticipant())
            ->setReward($reward)
            ->setUser(new User());

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $reward = $rewardManager->addMember($participant);

        $this->assertEquals($mainChange->add($bonusChange)->getAmount(), $reward->getPrice()->getAmount());
        $this->assertEquals($mainChange->add($bonusChange)->getCurrency(), $reward->getPrice()->getCurrency());
    }

    public function testRefundReward(): void
    {
        $rewardUser = (new User())
            ->setEmail('rewardOwner@gmail.com');
        $token = $this->createToken()
            ->setProfile((new Profile($rewardUser)));
        $participantUser = (new User())
            ->setEmail('participant@gmail.com');
        $reward = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('100', new Currency(Symbols::TOK)))
            ->setToken($token);
        $participant = (new RewardParticipant())
            ->setReward($reward)
            ->setPrice($reward->getPrice())
            ->setUser($participantUser);
        $reward->addParticipant($participant);

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);

        $balanceHandler
            ->expects($this->exactly(2))
            ->method('update')
            ->withConsecutive(
                [
                    $participantUser,
                    $this->anything(),
                    $reward->getPrice(),
                    $this->equalTo(RewardManager::REWARD_REFUND_ID),
                ],
                [
                    $rewardUser,
                    $this->anything(),
                    $reward->getPrice()->negative(),
                    $this->equalTo(RewardManager::REWARD_REFUND_ID),
                ]
            );

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->refundReward($reward, $participant);

        $this->assertEquals(RewardParticipant::REFUNDED_STATUS, $participant->getStatus());
    }

    public function testRefundRewardWithBonus(): void
    {
        $price = new Money('90', new Currency(Symbols::TOK));
        $bonusPrice = new Money('10', new Currency(Symbols::TOK));
        $fullPrice = new Money('100', new Currency(Symbols::TOK));
        $rewardUser = (new User())
            ->setEmail('rewardOwner@gmail.com');
        $token = $this->createToken()
            ->setProfile((new Profile($rewardUser)));
        $participantUser = (new User())
            ->setEmail('participant@gmail.com');
        $reward = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice($fullPrice)
            ->setToken($token);
        $participant = (new RewardParticipant())
            ->setReward($reward)
            ->setPrice($price)
            ->setBonusPrice($bonusPrice)
            ->setUser($participantUser);
        $reward->addParticipant($participant);

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);

        $balanceHandler
            ->expects($this->exactly(2))
            ->method('update')
            ->withConsecutive(
                [
                    $participantUser,
                    $this->anything(),
                    $price,
                    $this->equalTo(RewardManager::REWARD_REFUND_ID),
                ],
                [
                    $rewardUser,
                    $this->anything(),
                    $reward->getPrice()->negative(),
                    $this->equalTo(RewardManager::REWARD_REFUND_ID),
                ]
            );

        $balanceHandler
            ->expects($this->once())
            ->method('depositBonus')
            ->with(
                $participantUser,
                $this->anything(),
                $bonusPrice,
                $this->equalTo(RewardManager::REWARD_REFUND_ID)
            );

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->refundReward($reward, $participant);

        $this->assertEquals(RewardParticipant::REFUNDED_STATUS, $participant->getStatus());
    }

    public function testSetParticipantStatus(): void
    {
        $rewardUser = (new User())
            ->setEmail('rewardOwner@gmail.com');
        $token = $this->createToken()
            ->setProfile((new Profile($rewardUser)));
        $participantUser = (new User())
            ->setEmail('participant@gmail.com');
        $reward = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setTitle('foo')
            ->setQuantity(10)
            ->setPrice(new Money('100', new Currency(Symbols::TOK)))
            ->setToken($token);
        $participant = (new RewardParticipant())
            ->setReward($reward)
            ->setPrice($reward->getPrice())
            ->setUser($participantUser);
        $reward->addParticipant($participant);

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->setParticipantStatus($participant, RewardParticipant::DELIVERED_STATUS);

        $this->assertEquals(RewardParticipant::DELIVERED_STATUS, $participant->getStatus());
    }

    public function testSaveRewardWithTypeBountyChangedQuantityWithAmountToFreezeHigher(): void
    {
        $oldPrice = new Money('10', new Currency(Symbols::TOK));
        $oldQuantity = 10;
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $newReward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setToken($token)
            ->setQuantity(15)
            ->addParticipant($this->createMock(RewardParticipant::class));
        $newReward->setFrozenAmount(
            $oldPrice->multiply($oldQuantity - $newReward->getParticipants()->count())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn(new Money('100000000', new Currency(Symbols::TOK)));

        $amountToWithdraw = $newReward
            ->getPrice()
            ->multiply($newReward->getQuantity() - $newReward->getParticipants()->count())
            ->subtract($newReward->getFrozenAmount());

        $expectedFrozenAmount = $newReward->getFrozenAmount()->add($amountToWithdraw);

        $balanceHandler
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->anything(),
                $this->anything(),
                $amountToWithdraw->negative(),
                $this->equalTo('reward_update')
            );

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->saveReward($newReward, $oldPrice, $oldQuantity);

        $this->assertEquals($expectedFrozenAmount, $newReward->getFrozenAmount());
    }

    public function testSaveRewardWithTypeBountyChangedPriceQuantityWithAmountToFreezeLower(): void
    {
        $oldPrice = new Money('10', new Currency(Symbols::TOK));
        $oldQuantity = 10;
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $newReward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setToken($token)
            ->setQuantity(5)
            ->addParticipant($this->createMock(RewardParticipant::class));
        $newReward->setFrozenAmount(
            $oldPrice->multiply($oldQuantity - $newReward->getParticipants()->count())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn(new Money('100000', new Currency(Symbols::TOK)));

        $amountToFreeze = $newReward->getPrice()->multiply(
            $newReward->getQuantity() - $newReward->getParticipants()->count()
        );

        $amountToDeposit = $newReward->getFrozenAmount()->subtract($amountToFreeze);

        $balanceHandler
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->anything(),
                $this->anything(),
                $amountToDeposit,
                $this->equalTo('reward_update')
            );

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $rewardManager->saveReward($newReward, $oldPrice, $oldQuantity);

        $this->assertEquals($amountToFreeze, $newReward->getFrozenAmount());
    }

    public function testSaveRewardTypeRewardExceptionNotEnoughAmount(): void
    {
        $oldPrice = new Money('10', new Currency(Symbols::TOK));
        $oldQuantity = 10;
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $newReward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice(new Money('10', new Currency(Symbols::TOK)))
            ->setToken($token)
            ->setQuantity(15)
            ->addParticipant($this->createMock(RewardParticipant::class));
        $newReward->setFrozenAmount(
            $oldPrice->multiply($oldQuantity - $newReward->getParticipants()->count())
        );

        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->once())
            ->method('exchangeBalance')
            ->willReturn(new Money('10', new Currency(Symbols::TOK)));

        $balanceHandler->expects($this->never())->method('update');
        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $this->createMock(EntityManagerInterface::class)
        );

        $this->expectException(NotEnoughAmountException::class);
        $rewardManager->saveReward($newReward, $oldPrice, $oldQuantity);
    }

    public function testAcceptVolunteerWithRemoveVolunteers(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $price = new Money('10', new Currency(Symbols::TOK));
        $reward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice($price)
            ->setToken($token)
            ->setQuantity(3)
            ->addParticipant(new RewardParticipant())
            ->addParticipant(new RewardParticipant())
            ->addVolunteer((new RewardVolunteer()))
            ->addVolunteer((new RewardVolunteer()))
            ->addVolunteer((new RewardVolunteer()));
        $reward->setFrozenAmount($reward->getPrice()->multiply($reward->getQuantity()));

        $volunteer = (new RewardVolunteer())
            ->setReward($reward)
            ->setPrice($price)
            ->setUser($token->getOwner());
        $reward->addVolunteer($volunteer);

        $balanceResult = $this->createBalanceResult('1000000');
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->never())
            ->method('balance')
            ->willReturn($balanceResult);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))->method('remove');

        $this->assertEquals(2, $reward->getParticipants()->count());
        $this->assertEquals(4, $reward->getVolunteers()->count());

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $entityManager
        );
        $expectedFrozenAmount = new Money('30', new Currency(Symbols::TOK));

        $modifiedReward = $rewardManager->acceptMember($volunteer);

        $this->assertEquals(3, $modifiedReward->getParticipants()->count());
        $this->assertEquals($expectedFrozenAmount, $modifiedReward->getFrozenAmount());
    }

    public function testCompleteVolunteer(): void
    {
        $token = $this->createToken()
            ->setProfile((new Profile(new User())));
        $price = new Money('10', new Currency(Symbols::TOK));
        $reward = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice($price)
            ->setToken($token)
            ->setQuantity(3)
            ->addParticipant(new RewardParticipant())
            ->addVolunteer((new RewardVolunteer()))
            ->addVolunteer((new RewardVolunteer()))
            ->addVolunteer((new RewardVolunteer()));
        $reward->setFrozenAmount($reward->getPrice()->multiply($reward->getQuantity()));

        $participant = (new RewardParticipant())
            ->setReward($reward)
            ->setPrice($price)
            ->setUser($token->getOwner())
            ->setStatus(RewardParticipant::NOT_COMPLETED_STATUS);
        $reward->addParticipant($participant);

        $balanceResult = $this->createBalanceResult('1000000');
        $balanceHandler = $this->createMock(BalanceHandlerInterface::class);
        $balanceHandler
            ->expects($this->never())
            ->method('balance')
            ->willReturn($balanceResult);

        $balanceHandler
            ->expects($this->once())
            ->method('update')
            ->with(
                $this->anything(),
                $this->anything(),
                $reward->getPrice(),
                'reward_participate'
            );

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->assertEquals(2, $reward->getParticipants()->count());
        $this->assertEquals(3, $reward->getVolunteers()->count());

        $rewardManager = $this->createRewardManager(
            $this->createMock(RewardRepository::class),
            $balanceHandler,
            $entityManager
        );
        $expectedFrozenAmount = new Money('20', new Currency(Symbols::TOK));

        $modifiedReward = $rewardManager->completeMember($participant);

        $this->assertEquals(2, $modifiedReward->getParticipants()->count());
        $this->assertEquals($expectedFrozenAmount, $modifiedReward->getFrozenAmount());
    }

    private function createBalanceResult(string $available): BalanceResult
    {
        return BalanceResult::success(
            new Money($available, new Currency(Symbols::TOK)),
            new Money('0', new Currency(Symbols::TOK)),
            new Money('0', new Currency(Symbols::TOK))
        );
    }

    private function createRewardManager(
        RewardRepository $rewardRepository,
        BalanceHandlerInterface $balanceHandler,
        EntityManagerInterface $entityManager
    ): RewardManager {
        $slug = $this->createMock(SlugConverterInterface::class);
        $slug->method('convert')->willReturn('foo');

        return new RewardManager(
            $rewardRepository,
            $this->createMock(RewardVolunteerRepository::class),
            $this->createMock(RewardParticipantRepository::class),
            $entityManager,
            $balanceHandler,
            $this->createMock(UserActionLogger::class),
            $this->createMock(MoneyWrapperInterface::class),
            $slug
        );
    }

    private function createToken(): Token
    {
        return new Token();
    }

    public function rewardsForGetUnfinishedProvider(): array
    {
        $reward1NF = (new Reward())
            ->setType(Reward::TYPE_BOUNTY)
            ->setPrice(new Money('1', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setQuantity(1)
            ->addVolunteer($this->createMock(RewardVolunteer::class))
            ->addVolunteer($this->createMock(RewardVolunteer::class))
            ->addVolunteer($this->createMock(RewardVolunteer::class))
            ->addVolunteer($this->createMock(RewardVolunteer::class));

        $reward2F = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setPrice(new Money('1', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setQuantity(2)
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class));

        $reward3NF = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setPrice(new Money('1', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setQuantity(3)
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class));

        $reward4F = (new Reward())
            ->setType(Reward::TYPE_REWARD)
            ->setPrice(new Money('1', new Currency(Symbols::TOK)))
            ->setTitle('foo')
            ->setQuantity(5)
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class))
            ->addParticipant($this->createMock(RewardParticipant::class));

        return [
            'all finished and result should be 0' => [[$reward2F, $reward4F], 0],
            '2 unfinished and 2 finished, result 2' => [[$reward1NF, $reward2F, $reward3NF, $reward4F], 2],
            'all unfinished, result 2' => [[$reward1NF, $reward3NF], 2],
        ];
    }
}
