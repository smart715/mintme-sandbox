<?php declare(strict_types = 1);

namespace App\Controller;

use App\Exception\NotFoundPairException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CoinController extends Controller
{
    /** @var CryptoManagerInterface */
    private $cryptoManager;

    /** @var MarketFactoryInterface */
    private $marketFactory;

    public function __construct(
        NormalizerInterface $normalizer,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory
    ) {
        parent::__construct($normalizer);

        $this->cryptoManager = $cryptoManager;
        $this->marketFactory = $marketFactory;
    }

    /** @Route("/coin/{base}/{quote}", name="coin", defaults={"quote"="mintme"}, options={"expose"=true,"2fa_progress"=false}) */
    public function pair(string $base, string $quote): Response
    {
        // rebranding
        if ('WEB' === mb_strtoupper($quote)) {
            return $this->redirectToRoute('coin', [
                'base' => mb_strtoupper($base),
                'quote' => 'MINTME',
            ]);
        }

        $base = str_replace('MINTME', 'WEB', mb_strtoupper($base));
        $quote = str_replace('MINTME', 'WEB', mb_strtoupper($quote));

        $base = $this->cryptoManager->findBySymbol($base);
        $quote = $this->cryptoManager->findBySymbol(strtoupper($quote));

        if (null === $base          ||
            null === $quote         ||
            $base === $quote        ||
            !$base->isTradable()    ||
            !$quote->isExchangeble()
        ) {
            throw new NotFoundPairException();
        }

        $market = $this->marketFactory->create($base, $quote);

        return $this->render('pages/pair.html.twig', [
            'market' => $this->normalize($market),
            'isOwner' => false,
            'showIntro' => false,
            'hash' => $this->getUser() ?
                $this->getUser()->getHash() :
                '',
            'precision' => $quote->getShowSubunit(),
        ]);
    }
}
