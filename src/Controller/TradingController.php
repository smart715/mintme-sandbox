<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Token\Token;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManager;
use App\Repository\TokenRepository;
use Symfony\Component\HttpFoundation\Request;
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
        string $page,
        Request $request,
        CryptoManagerInterface $cryptoManager,
    ): Response {
        $btcCrypto = $cryptoManager->findBySymbol(Token::BTC_SYMBOL);
        $webCrypto = $cryptoManager->findBySymbol(Token::WEB_SYMBOL);

        return $this->render('pages/trading.html.twig', [
            'tokensCount' => $this->getTokenRepository()->count(['isBlocked' => false]),
            'btcImage' => $btcCrypto->getImage(),
            'mintmeImage' => $webCrypto->getImage(),
            'tokenImage' => Image::defaultImage(Image::DEFAULT_TOKEN_IMAGE_URL),
            'page' => $page,
            'sort' => $request->query->get('sort'),
            'order' => 'ASC' !== $request->query->get('order'),
            'filterForTokens'=> MarketStatusManager::FILTER_FOR_TOKENS,
        ]);
    }

    private function getTokenRepository(): TokenRepository
    {
        return $this->getDoctrine()->getManager()->getRepository(Token::class);
    }
}
