<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\DiscordConfig;
use App\Manager\DiscordConfigManager;
use App\Repository\DiscordConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DiscordConfigManagerTest extends TestCase
{
    public function testDisable(): void
    {
        $config = (new DiscordConfig())->setEnabled(true)->setSpecialRolesEnabled(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($config);

        $dcm = new DiscordConfigManager(
            $em,
            $this->createMock(DiscordConfigRepository::class)
        );

        $dcm->disable($config);

        $this->assertFalse($config->getEnabled());
        $this->assertFalse($config->getSpecialRolesEnabled());
    }

    public function testFindByGuildId(): void
    {
        $config = new DiscordConfig();

        $repo = $this->createMock(DiscordConfigRepository::class);
        $repo->method('findOneBy')->willReturn($config);

        $dcm = new DiscordConfigManager(
            $this->createMock(EntityManagerInterface::class),
            $repo
        );

        $this->assertEquals($config, $dcm->findByGuildId(1));
    }
}
