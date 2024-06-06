<?php declare(strict_types = 1);

namespace App\Controller\News;

use App\Entity\News\Post;
use App\Manager\PostNewsManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PostNewsController extends AbstractController
{
    private PostNewsManagerInterface $postNewsManager;
    private CacheInterface $cache;

    public function __construct(
        PostNewsManagerInterface $postNewsManager,
        CacheInterface $cache
    ) {
        $this->postNewsManager = $postNewsManager;
        $this->cache = $cache;
    }

    /** @Route("/recommended-post-news", name="recommended_post_news") */
    public function recommendedPost(): Response
    {
        $postNews = $this->cache->get(
            'recommended_post_id',
            function (ItemInterface $item) {
                $item->expiresAfter(
                    (int)$this->getParameter(Post::RECOMMENDED_CACHE_KEY)
                );

                return $this->postNewsManager->getRandomPostNews(4);
            }
        );

        return $this->render('recommended_post_news.html.twig', [
            'postNews' => $postNews,
        ]);
    }
}
