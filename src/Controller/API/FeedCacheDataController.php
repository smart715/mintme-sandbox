<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\News\Post;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\PostNewsManagerInterface;
use App\Manager\TokenManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @Rest\Route("/api/feed-cache-data")
 */
class FeedCacheDataController extends APIController
{
    private CacheInterface $cache;
    private PostNewsManagerInterface $postNewsManager;

    public function __construct(
        CacheInterface $cache,
        PostNewsManagerInterface $postNewsManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory
    ) {
        $this->cache = $cache;
        $this->postNewsManager = $postNewsManager;

        parent::__construct($cryptoManager, $tokenManager, $marketFactory);
    }
    /**
     * This method was created to be used externally in Explorer and Pool.
     *
     * @Rest\View()
     * @Rest\Get("/get", name="feed_cache_data", options={"expose"=true})
     * @Rest\QueryParam(
     *      name="limit",
     *      allowBlank=false,
     *      strict=true,
     *      requirements=@Assert\Range(min="1", max="10"),
     * )
     * @Rest\QueryParam(
     *      name="feedCacheType",
     *      allowBlank=false,
     *      strict=true,
     *      requirements="(feed_cache_explorer|feed_cache_pool)"
     * )
     * @return View
     * @param ParamFetcherInterface $request
     */
    public function getRandomFeedData(ParamFetcherInterface $request): View
    {
        $limit = (int)$request->get('limit');
        $feedCacheType = (string)$request->get('feedCacheType');

        if (!$feedCacheType) {
            return $this->view([]);
        }

        $postNewsAndTokens = $this->cache->get(
            $feedCacheType,
            function (ItemInterface $item) use ($limit) {
                $item->expiresAfter(
                    (int)$this->getParameter(Post::FOOTER_CACHE_KEY)
                );
                
                return [
                    'tokens' => $this->tokenManager->getRandomTokens($limit),
                    'postNews' => $this->postNewsManager->getRandomPostNews($limit),
                ];
            }
        );

        return $this->view($postNewsAndTokens, Response::HTTP_OK);
    }
}
