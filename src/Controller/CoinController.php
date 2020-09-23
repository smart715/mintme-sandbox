<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\NotFoundPairException;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
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
        if (Token::WEB_SYMBOL === mb_strtoupper($quote)) {
            return $this->redirectToRoute('coin', [
                'base' => mb_strtoupper($base),
                'quote' => Token::MINTME_SYMBOL,
            ]);
        }

        $base = str_replace(Token::MINTME_SYMBOL, Token::WEB_SYMBOL, mb_strtoupper($base));
        $quote = str_replace(Token::MINTME_SYMBOL, Token::WEB_SYMBOL, mb_strtoupper($quote));

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

        /** @var  User|null $user */
        $user = $this->getUser();

        return $this->render('pages/pair.html.twig', [
            'market' => $this->normalize($market),
            'isOwner' => false,
            'showTrade' => true,
            'showDonation' => false,
            'hash' => $user ? $user->getHash() : '',
            'precision' => $quote->getShowSubunit(),
            'isTokenPage' => false,
            'tab' => 'trade',
        ]);
    }
}
