<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\News\Post;
use App\Manager\PostNewsManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Utils\Converter\FriendlyUrlConverterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class FooterController extends AbstractController
{
    private CacheInterface $cache;
    private PostNewsManagerInterface $postNewsManager;
    private TokenManagerInterface $tokenManager;
    private FriendlyUrlConverterInterface $urlConverter;
    private const KEY_HOME = 'home';

    public function __construct(
        CacheInterface $cache,
        PostNewsManagerInterface $postNewsManager,
        TokenManagerInterface $tokenManager,
        FriendlyUrlConverterInterface $urlConverter
    ) {
        $this->cache = $cache;
        $this->postNewsManager = $postNewsManager;
        $this->tokenManager = $tokenManager;
        $this->urlConverter = $urlConverter;
    }

    /** @Route("/footer-get-links", name="footer_get_links") */
    public function getLinks(): Response
    {
        $createKey = $this->urlConverter->generateKey($_SERVER['REQUEST_URI']);
        
        $keyCache = '' === $createKey
            ? self::KEY_HOME
            : $createKey;

        $tokensAndPostNews = $this->cache->get(
            $keyCache,
            function (ItemInterface $item) {
                $item->expiresAfter(
                    (int)$this->getParameter(Post::FOOTER_CACHE_KEY)
                );
                
                return [
                    'tokens' => $this->tokenManager->getRandomTokens(2),
                    'postNews' => $this->postNewsManager->getRandomPostNews(2),
                ];
            }
        );

        return $this->render('footer_news.html.twig', [
            'tokens' => $tokensAndPostNews['tokens'],
            'postNews' => $tokensAndPostNews['postNews'],
        ]);
    }
}
