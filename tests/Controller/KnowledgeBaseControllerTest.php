<?php declare(strict_types = 1);

namespace App\Tests\Controller;

use App\Entity\KnowledgeBase\Category;
use App\Entity\KnowledgeBase\KnowledgeBase;
use Symfony\Bundle\FrameworkBundle\Client;

class KnowledgeBaseControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testShowAll(): void
    {
        $catName = $this->generateString();
        $fooUrl = $this->generateString();

        $cat = new Category();
        $cat->setName($catName);
        $this->em->persist($cat);

        $this->truncateEntities([KnowledgeBase::class]);

        $this->createKB($cat, $fooUrl, 'foo description');

        $this->client->request('GET', '/kb');

        $this->assertContains($fooUrl, $this->client->getResponse()->getContent());
        $this->assertGreaterThan(
            0,
            $this->client->getCrawler()->filter('a[href="/kb/'. $fooUrl .'"]')->count()
        );
    }

    public function testShowAllIfNoKB(): void
    {
        $this->truncateEntities([KnowledgeBase::class]);

        $this->client->request('GET', '/kb');

        $this->assertContains(
            'No articles found',
            $this->client->getResponse()->getContent()
        );
    }

    public function testShow(): void
    {
        $catName = $this->generateString();
        $fooUrl = $this->generateString();

        $cat = new Category();
        $cat->setName($catName);
        $this->em->persist($cat);

        $this->truncateEntities([KnowledgeBase::class]);

        $this->createKB($cat, $fooUrl, 'foo description');

        $this->client->request('GET', '/kb/' . $fooUrl);

        $res = $this->client->getResponse()->getContent();

        $this->assertContains($fooUrl, $res);
        $this->assertContains('foo description', $res);
        $this->assertGreaterThan(
            0,
            $this->client->getCrawler()->filter('a[href="/kb"]')->count()
        );
    }

    public function testShowIfNotFound(): void
    {
        $this->client->request('GET', '/kb/FooNotExistTest');

        $this->assertTrue($this->client->getResponse()->isNotFound());
    }

    private function createKB(Category $cat, string $title, string $desc): void
    {
        $kb = new KnowledgeBase();
        $kb->setCategory($cat);
        $kb->setTitle($title);
        $kb->setUrl($title);
        $kb->setDescription($desc);
        $this->em->persist($kb);
        $this->em->flush();
    }
}
