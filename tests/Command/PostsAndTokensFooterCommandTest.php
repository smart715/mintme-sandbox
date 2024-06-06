<?php declare(strict_types = 1);

namespace App\Tests\Command;

use App\Command\PostsAndTokensFooterCommand;
use App\Entity\News\Post;
use App\Entity\Token\Token;
use App\Manager\PostNewsManagerInterface;
use App\Manager\TokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PostsAndTokensFooterCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;

    public function setUp(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $application->add(new PostsAndTokensFooterCommand(
            $this->mockTokenManager(),
            $this->mockPostNewsManager(),
            $this->mockCache(60),
        ));

        $command = $application->find('app:update-post-and-tokens');

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
            ->with(2)
            ->willReturn(
                [   $this->mockPost(),
                    $this->mockPost(),
                ]
            );

        return $postNewsManager;
    }

    private function mockTokenManager(): TokenManagerInterface
    {
        $tokenManager = $this->createMock(TokenManagerInterface::class);
        $tokenManager
            ->method('getRandomTokens')
            ->with(2)
            ->willReturn(
                [   $this->mockToken(),
                    $this->mockToken(),
                ]
            );

        return $tokenManager;
    }

    private function mockToken(): Token
    {
        return $this->createMock(Token::class);
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

                return [
                    'tokens' => $this->mockTokenManager(),
                    'postNews' => $this->mockPostNewsManager(),
                ];
            });

        return $cache;
    }
}
