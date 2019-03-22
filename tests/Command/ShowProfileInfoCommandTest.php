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
    public function testExecute(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $application->add(new ShowProfileInfoCommand(
            $this->profileManagerMock()
        ));

        $command = $application->find('app:profile:info');
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

    private function profileManagerMock(): ProfileManagerInterface
    {
        $profileManagerMock = $this->createMock(profileManagerInterface::class);
        $profileManagerMock->expects($this->once())
            ->method('findByEmail')
            ->with($this->equalTo('info@coinimp.com'))
            ->willReturn($this->profileMock())
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
