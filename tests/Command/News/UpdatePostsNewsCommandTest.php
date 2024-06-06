<?php declare(strict_types = 1);

namespace App\Tests\Command\News;

use App\Command\News\UpdatePostsNewsCommand;
use App\Entity\News\Post;
use App\Manager\PostNewsManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UpdatePostsNewsCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new UpdatePostsNewsCommand(
            $this->mockPostNewsManager(),
            $this->mockCache(60),
        ));

        $command = $application->find('app:update-posts-news');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([
            'time' => '60',
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString("The cache has been updated.", $output);
    }

    private function mockPostNewsManager(): PostNewsManagerInterface
    {
        $postNewsManager = $this->createMock(PostNewsManagerInterface::class);
        $postNewsManager
            ->method('getRandomPostNews')
            ->with(4)
            ->willReturn(
                [
                    $this->mockPost(),
                    $this->mockPost(),
                    $this->mockPost(),
                    $this->mockPost(),
                ]
            );

        return $postNewsManager;
    }

    private function mockPost(): Post
    {
        return $this->createMock(Post::class);
    }

    private function mockCache(int $time): CacheInterface
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->method('get')
            ->willReturnCallback(function ($key, $callback) use ($time) {
                $item = $this->createMock(ItemInterface::class);
                $item
                    ->expects($this->once())
                    ->method('expiresAfter')
                    ->with($time)
                    ->willReturn($item);

                $callback($item);

                return $this->mockPostNewsManager();
            });

        return $cache;
    }
}
