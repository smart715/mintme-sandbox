<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\News\Post;
use App\Manager\PostNewsManager;
use App\Repository\News\PostRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PostNewsManagerTest extends TestCase
{
    public function testGetRandomPostNews(): void
    {
        $limit = 2;
        $posts = [
            $this->mockPost(),
            $this->mockPost(),
        ];

        $postRepository = $this->mockPostRepository();
        $postRepository
            ->expects($this->once())
            ->method('getRandomPost')
            ->with($limit)
            ->willReturn($posts);

        $postNewsManager = new PostNewsManager($postRepository);

        $this->assertEquals($posts, $postNewsManager->getRandomPostNews($limit));
    }

    /** @return MockObject|Post */
    private function mockPost(): Post
    {
        return $this->createMock(Post::class);
    }

    /** @return MockObject|PostRepository */
    private function mockPostRepository(): PostRepository
    {
        return $this->createMock(PostRepository::class);
    }
}
