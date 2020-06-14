<?php declare(strict_types = 1);

namespace App\Tests\Manager;

use App\Entity\Post;
use App\Manager\PostManager;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class PostManagerTest extends TestCase
{
    public function testGetById(): void
    {
        $p = $this->createMock(Post::class);

        $pr = $this->createMock(PostRepository::class);
        $pr->method('find')->willReturn($p);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Post::class)->willReturn($pr);

        $pm = new PostManager($em);

        $this->assertEquals($p, $pm->getById(1));
    }
}
