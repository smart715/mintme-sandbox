<?php

namespace App\Controller;

use App\Exception\NotFoundPairException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PairController extends Controller
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

    /** @Route("/coin/{base}/{quote}", defaults={"quote"="web"}) */
    public function pair(string $base, string $quote): Response
    {
        $base = $this->cryptoManager->findBySymbol(strtoupper($base));
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
        ]);
    }
}
