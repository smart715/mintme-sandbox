<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\DiscordRole;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Model\BalanceResult;
use App\Manager\DiscordRoleManager;
use App\Manager\TokenManagerInterface;
use App\Repository\DiscordRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class DiscordRoleManagerTest extends TestCase
{
    public function testFindRoleOfUser(): void
    {
        $user = $this->createMock(User::class);
        $token = $this->createMock(Token::class);

        $m = new Money('1', new Currency('TOK'));

        $br = $this->createMock(BalanceResult::class);
        $br->expects($this->once())->method('getAvailable')->willReturn($m);

        $bh = $this->createMock(BalanceHandlerInterface::class);
        $bh->expects($this->once())->method('balance')->with($user, $token)->willReturn($br);

        $tm = $this->createMock(TokenManagerInterface::class);
        $tm->expects($this->once())->method('getRealBalance')->with($token, $br, $user)->willReturn($br);

        $repo = $this->createMock(DiscordRoleRepository::class);
        $repo->expects($this->once())->method('findByTokenAndAmount')->with($token, $m);

        $drm = new DiscordRoleManager(
            $repo,
            $bh,
            $tm,
            $this->createMock(EntityManagerInterface::class)
        );

        $drm->findRoleOfUser($user, $token);
    }

    public function testRemoveRole(): void
    {
        $dr = $this->createMock(DiscordRole::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($dr);
        $em->expects($this->once())->method('flush');

        $drm = new DiscordRoleManager(
            $this->createMock(DiscordRoleRepository::class),
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(TokenManagerInterface::class),
            $em
        );

        $drm->removeRole($dr);
    }

    public function testRemoveRoles(): void
    {
        $roles = [];

        for ($i = 0; $i < 5; $i++) {
            $roles[$i] = $this->createMock(DiscordRole::class);
        }

        $token = $this->createMock(Token::class);
        $token->method('getDiscordRoles')->willReturn($roles);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(5))->method('remove')->withConsecutive(...array_chunk($roles, 1));
        $em->expects($this->once())->method('flush');

        $drm = new DiscordRoleManager(
            $this->createMock(DiscordRoleRepository::class),
            $this->createMock(BalanceHandlerInterface::class),
            $this->createMock(TokenManagerInterface::class),
            $em
        );

        $drm->removeAllRoles($token);
    }
}
