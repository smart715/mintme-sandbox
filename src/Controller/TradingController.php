<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Image;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManager;
use App\Manager\MarketStatusManagerInterface;
use App\Utils\Symbols;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TradingController extends Controller
{
    public function __construct(
        NormalizerInterface $normalizer
    ) {
        parent::__construct($normalizer);
    }

    /**
     * @Route("/trading/{page}",
     *     defaults={"page"="1"},
     *     requirements={"page"="\d+"},
     *     name="trading",
     *     options={"expose"=true,
     *          "sitemap" = true,
     *          "2fa_progress"=false
     *     }
     * )
     */
    public function trading(
        int $page,
        CryptoManagerInterface $cryptoManager,
        MarketStatusManagerInterface $marketStatusManager
    ): Response {
        $btcCrypto = $cryptoManager->findBySymbol(Symbols::BTC);
        $webCrypto = $cryptoManager->findBySymbol(Symbols::WEB);

        $sort = MarketStatusManager::SORT_MONTH_VOLUME;
        $order = 'DESC';
        $filter = MarketStatusManager::FILTER_DEPLOYED_ONLY_MINTME;
        $tokensOnPage = (int)$this->getParameter('tokens_on_page');

        $markets = $marketStatusManager->getMarketsInfo(
            $tokensOnPage * ($page - 1),
            $tokensOnPage,
            'monthVolume',
            'DESC',
            $filter,
            null
        );

        foreach ($markets as $name => $market) {
            $market = $this->normalize($market, ['Default','API']);
            $markets[$name] = $market;
        }

        return $this->render('pages/trading.html.twig', [
            'tokensCount' => $marketStatusManager->getMarketsCount(
                MarketStatusManager::FILTER_DEPLOYED_ONLY_MINTME
            ),
            'btcImage' => $btcCrypto->getImage(),
            'mintmeImage' => $webCrypto->getImage(),
            'tokenImage' => Image::defaultImage(Image::DEFAULT_TOKEN_IMAGE_URL),
            'page' => $page,
            'sort' => $sort,
            'order' => $order,
            'filterForTokens'=> MarketStatusManager::FILTER_FOR_TOKENS,
            'markets' => $markets['markets'] ?? $markets,
            'rows' => $marketStatusManager->getMarketsCount($filter),
            'perPage' => $tokensOnPage,
        ]);
    }
}
