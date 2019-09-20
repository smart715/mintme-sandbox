<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Exchange\Market;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Repository\TokenRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Security(expression="is_granted('prelaunch')")
 */
class TradingController extends Controller
{
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
    public function trading(string $page): Response
    {
        return $this->render('pages/trading.html.twig', [
            'tokensCount' => $this->getTokenRepository()->count([]),
            'page' => $page,
        ]);
    }

    private function getTokenRepository(): TokenRepository
    {
        return $this->getDoctrine()->getManager()->getRepository(Token::class);
    }
}
