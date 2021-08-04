<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundVotingException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Utils\BaseQuote;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/voting")
 */
class VotingController extends Controller
{
    private VotingManagerInterface $votingManager;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private TranslatorInterface $translator;
    private ?Crypto $mintme = null; //phpcs:disable

    public function __construct(
        VotingManagerInterface $votingManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        TranslatorInterface $translator,
        NormalizerInterface $normalizer
    ) {
        $this->votingManager = $votingManager;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->translator = $translator;

        $this->mintme = $this->cryptoManager->findBySymbol(Symbols::WEB);

        parent::__construct($normalizer);
    }

    /**
     * @Route("", name="voting")
     */
    public function voting(): Response
    {
        return $this->renderVoting();
    }

    /**
     * @Route("/create", name="create_voting", options={"expose"=true})
     */
    public function create(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $balance = $this->balanceHandler->balance(
            $user,
            Token::getFromCrypto($this->mintme)
        )->getAvailable();

        $min = (float)$this->getParameter('dm_min_amount');

        if ((float)$this->moneyWrapper->format($balance) < $min) {
            throw new ForbiddenException(
                $this->translator->trans('voting.create.min_amount_required', [
                    '%amount%' => $min,
                    '%currency%' => Symbols::MINTME,
                ])
            );
        }

        return $this->renderVoting([
            'activePage' => 'create_voting',
        ]);
    }

    /**
     * @Route("/{slug}", name="show_voting", options={"expose"=true})
     */
    public function show(string $slug): Response
    {
        $voting = $this->votingManager->getBySlugForTradable($slug, $this->mintme);

        if (!$voting) {
            throw new NotFoundVotingException();
        }

        return $this->renderVoting([
            'voting' => $this->normalize($voting),
            'activePage' => 'show_voting',
        ]);
    }

    private function renderVoting(array $extraData = []): Response
    {
        $crypto = $this->cryptoManager->findBySymbol(Symbols::BTC);
        $market =  $this->marketFactory->create($this->mintme, $crypto);
        $market = BaseQuote::reverseMarket($market);

        return $this->render('pages/voting.html.twig', array_merge(
            [
                'crypto' => $this->mintme,
                'votings' => $this->normalize($this->mintme->getVotings()),
                'minAmount' => (float)$this->getParameter('dm_min_amount'),
                'market' => $this->normalize($market),
                'precision' => $this->mintme->getShowSubunit(),
            ],
            $extraData
        ));
    }
}
