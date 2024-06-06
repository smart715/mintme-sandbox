<?php declare(strict_types = 1);

namespace App\Controller;

use App\Config\VotingConfig;
use App\Entity\Crypto;
use App\Entity\User;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundVotingException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\BaseQuote;
use App\Utils\Symbols;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/voting")
 */
class VotingController extends Controller
{
    private VotingManagerInterface $votingManager;
    private CryptoManagerInterface $cryptoManager;
    private MarketFactoryInterface $marketFactory;
    private BalanceHandlerInterface $balanceHandler;
    private TranslatorInterface $translator;
    private VotingConfig $votingConfig;
    private ?Crypto $mintme = null; //phpcs:disable

    public const VOTING_LIST_BATCH_SIZE = 10;
    public const VOTING_LIST_COMPENSATION = 4;

    public function __construct(
        VotingManagerInterface $votingManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketFactory,
        BalanceHandlerInterface $balanceHandler,
        TranslatorInterface $translator,
        NormalizerInterface $normalizer,
        VotingConfig $votingConfig
    ) {
        $this->votingManager = $votingManager;
        $this->marketFactory = $marketFactory;
        $this->cryptoManager = $cryptoManager;
        $this->balanceHandler = $balanceHandler;
        $this->translator = $translator;
        $this->votingConfig = $votingConfig;

        $this->mintme = $this->cryptoManager->findBySymbol(Symbols::WEB);

        parent::__construct($normalizer);
    }

    /**
     * @Route("", name="voting", options={"expose"=true})
     */
    public function voting(Request $request): Response
    {
        $page = (int)$request->get('page') ?: 1;

        return $this->renderVoting([], $page);
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
            $this->mintme
        )->getAvailable();

        $minProposalAmount = $this->votingConfig->getProposalMinAmount();

        if ($balance->lessThan($minProposalAmount)) {
            throw new ForbiddenException(
                $this->translator->trans('voting.create.min_amount_required', [
                    '%amount%' => $this->votingConfig->getProposalMinAmountNumeric(),
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

    private function renderVoting(array $extraData = [], ?int $page = 1): Response
    {
        /** @var Crypto $crypto */
        $crypto = $this->cryptoManager->findBySymbol(Symbols::BTC);
        $market =  $this->marketFactory->create($this->mintme, $crypto);
        $market = BaseQuote::reverseMarket($market);

        $offset = 1 !== $page
            ? (($page - 1) * self::VOTING_LIST_BATCH_SIZE) + self::VOTING_LIST_COMPENSATION
            : 0;

        $limit = 1 === $page
            ? self::VOTING_LIST_BATCH_SIZE + self::VOTING_LIST_COMPENSATION
            : self::VOTING_LIST_BATCH_SIZE;

        $votings = $this->cryptoManager->getVotingByCryptoId(
            $this->mintme->getId(),
            $offset,
            $limit
        );

        $totalVotingCount = $this->cryptoManager->getVotingCountAll();

        return $this->render('pages/voting.html.twig', array_merge(
            [
                'crypto' => $this->mintme,
                'votings' => $this->normalize($votings),
                'page' => $page,
                'totalVotingCount' => $totalVotingCount,
                'totalVotingPages' => $this->getTotalVotingPages($totalVotingCount),
                'minAmountPropose' => $this->votingConfig->getProposalMinAmountNumeric(),
                'minAmountVote' => $this->votingConfig->getMinBalanceToVoteNumeric(),
                'market' => $this->normalize($market),
                'precision' => $this->mintme->getShowSubunit(),
            ],
            $extraData
        ));
    }

    private function getTotalVotingPages(int $votingCount): int
    {
        $votingsOnFirstPage = self::VOTING_LIST_BATCH_SIZE + self::VOTING_LIST_COMPENSATION;

        $votingsOnPage = $votingCount <= $votingsOnFirstPage
            ? $votingsOnFirstPage
            : self::VOTING_LIST_BATCH_SIZE;


        return (int)ceil($votingCount / $votingsOnPage);
    }
}
