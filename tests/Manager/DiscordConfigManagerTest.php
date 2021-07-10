<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Token\DiscordConfig;
use App\Manager\DiscordConfigManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DiscordConfigManagerTest extends TestCase
{
    public function testDisable(): void
    {
        $config = $this->createMock(DiscordConfig::class);
        $config->expects($this->once())->method('disable');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($config);

        $dcm = new DiscordConfigManager($em);

        $dcm->disable($config);
    }
}
