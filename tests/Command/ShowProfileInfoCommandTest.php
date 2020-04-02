<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\ShowProfileInfoCommand;
use App\Entity\Profile;
use App\Manager\ProfileManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ShowProfileInfoCommandTest extends KernelTestCase
{
    /** @var Application */
    private $app;

    public function setUp(): void
    {
        parent::setUp();

        $kernel = self::bootKernel();
        $this->app = new Application($kernel);
    }

    public function testExecute(): void
    {
        $this->app->add(new ShowProfileInfoCommand(
            $this->profileManagerMock()
        ));

        $command = $this->app->find('app:profile:info');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email'  => 'info@coinimp.com',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('First name       first name', $output);
        $this->assertContains('Last name        last name', $output);
        $this->assertContains('Email            info@coinimp.com', $output);
        $this->assertContains('Changes locked   No', $output);
    }

    public function testExecuteWithUnesistentEmail(): void
    {
        $this->app->add(new ShowProfileInfoCommand(
            $this->profileManagerMock(true)
        ));

        $command = $this->app->find('app:profile:info');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'email'  => 'info@coinimp.com',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertContains('Profile of \'info@coinimp.com\' not found', $output);
    }

    private function profileManagerMock(bool $null = false): ProfileManagerInterface
    {
        $profileManagerMock = $this->createMock(profileManagerInterface::class);
        $profileManagerMock->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('info@coinimp.com'))
            ->willReturn($null ? null : $this->profileMock())
        ;

        return $profileManagerMock;
    }

    private function profileMock(): Profile
    {
        $profileMock = $this->createMock(profile::class);
        $profileMock->expects($this->once())
            ->method('getFirstName')
            ->willReturn('first name')
        ;
        $profileMock->expects($this->once())
            ->method('getLastName')
            ->willReturn('last name')
        ;
        $profileMock->expects($this->once())
            ->method('getUserEmail')
            ->willReturn('info@coinimp.com')
        ;
        $profileMock->expects($this->once())
            ->method('isChangesLocked')
            ->willReturn(false)
        ;

        return $profileMock;
    }
}
