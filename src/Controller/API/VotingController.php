<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Config\UserLimitsConfig;
use App\Config\VotingConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\Voting\CryptoVoting;
use App\Entity\Voting\TokenVoting;
use App\Entity\Voting\UserVoting;
use App\Events\Activity\UserVotingEventActivity;
use App\Events\Activity\VotingEventActivity;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\MintmeVotingType;
use App\Form\VotingType;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserActionManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Manager\VotingOptionManagerInterface;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\ActionTypes;
use App\Utils\Converter\SlugConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @Rest\Route("/api/voting")
 */
class VotingController extends AbstractFOSRestController
{
    private EntityManagerInterface $entityManager;
    private TokenManagerInterface $tokenManager;
    private CryptoManagerInterface $cryptoManager;
    private VotingManagerInterface $votingManager;
    private VotingOptionManagerInterface $optionManager;
    private BalanceHandlerInterface $balanceHandler;
    private MoneyWrapperInterface $moneyWrapper;
    private TranslatorInterface $translator;
    private SlugConverterInterface $slugger;
    private UserLimitsConfig $userLimitsConfig;
    private VotingConfig $votingConfig;
    private UserActionManagerInterface $userActionManager;
    private EventDispatcherInterface $eventDispatcher;
    protected SessionInterface $session;

    public const VOTINGS_LIST_BATCH_SIZE = 10;
    public const VOTING_LIST_COMPENSATION = 4;

    use ViewOnlyTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        VotingManagerInterface $votingManager,
        VotingOptionManagerInterface $optionManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        TranslatorInterface $translator,
        SlugConverterInterface $slugger,
        UserLimitsConfig $userLimitsConfig,
        VotingConfig $votingConfig,
        UserActionManagerInterface $userActionManager,
        EventDispatcherInterface $eventDispatcher,
        SessionInterface $session
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->votingManager = $votingManager;
        $this->optionManager = $optionManager;
        $this->balanceHandler = $balanceHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->translator = $translator;
        $this->slugger = $slugger;
        $this->userLimitsConfig = $userLimitsConfig;
        $this->votingConfig = $votingConfig;
        $this->userActionManager = $userActionManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->session = $session;
    }

    /**
     * @Rest\View()
     * @Rest\Post("/store/{tokenName}", name="store_voting", options={"expose"=true})
     * @Rest\RequestParam(name="title", nullable=false)
     * @Rest\RequestParam(name="description", nullable=false)
     * @Rest\RequestParam(name="endDate", nullable=false)
     * @Rest\RequestParam(name="options", nullable=false)
     * @throws ApiNotFoundException
     * @throws AccessDeniedHttpException
     */
    public function store(
        string $tokenName,
        ParamFetcherInterface $request,
        EventDispatcherInterface $eventDispatcher
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var Token|Crypto|null $tradable */
        $tradable = $this->cryptoManager->findBySymbol($tokenName)
            ?? $this->tokenManager->findByName($tokenName);

        $votingCount = $this->userActionManager->getCountByUserAtDate(
            $user,
            ActionTypes::CREATE_VOTING,
            new \DateTimeImmutable(date('Y-m-d'))
        );
        $limitation = $this->userLimitsConfig->getMaxVotingsLimit();

        if ($votingCount >= $limitation) {
            throw new ApiForbiddenException($this->translator->trans('api.max_votings', ['%limit%' => $limitation]));
        }

        if (!$tradable) {
            throw new ApiNotFoundException();
        }

        if ($tradable instanceof Token) {
            $this->denyAccessUnlessGranted('interact', $tradable);
        }

        $fullAvailableBalance = $this->balanceHandler->balance(
            $user,
            $tradable
        )->getFullAvailable();

        $proposalMinAmount = $tradable instanceof Token
            ? $this->moneyWrapper->parse(
                $tradable->getTokenProposalMinAmount(),
                Symbols::TOK
            )
            : $this->votingConfig->getProposalMinAmount();

        $isOwner = $tradable instanceof Token
            ? $tradable->isOwner($user->getProfile()->getTokens())
            : false;

        if (!$isOwner && $fullAvailableBalance->lessThan($proposalMinAmount)) {
            throw new ApiForbiddenException();
        }

        if ($tradable instanceof Token) {
            $voting = new TokenVoting();
            $voting->setToken($tradable);
        } else {
            $voting = new CryptoVoting();
            $voting->setCrypto($tradable);
        }

        $voting->setCreator($user);

        $type = SYMBOLS::WEB === $tokenName
            ? MintmeVotingType::class
            : VotingType::class;

        $form = $this->createForm($type, $voting, ['csrf_protection' => false]);

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $slug = $this->slugger->convert(
            $voting->getTitle(),
            $this->votingManager->getRepository()
        );

        $voting->setSlug($slug);

        $this->entityManager->persist($voting);
        $this->entityManager->flush();

        $this->userActionManager->createUserAction($user, ActionTypes::CREATE_VOTING);

        if ($voting instanceof TokenVoting) {
            $this->eventDispatcher->dispatch(
                new VotingEventActivity($voting, ActivityTypes::PROPOSITION_ADDED),
                VotingEventActivity::NAME
            );
        }

        return $this->view([
            'voting' => $voting,
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/list/{tokenName}", name="list_voting", options={"expose"=true})
     * @Rest\QueryParam(name="offset", default=0)
     * @Rest\QueryParam(name="limit", default=null, nullable=true)
     * @param string $tokenName
     * @return View
     * @throws ApiNotFoundException
     */
    public function list(string $tokenName, ParamFetcherInterface $request): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        $offset = intval($request->get('offset'));

        $votingsOnPage = 0 < $offset
            ? self::VOTINGS_LIST_BATCH_SIZE
            : self::VOTINGS_LIST_BATCH_SIZE + self::VOTING_LIST_COMPENSATION;

        $limit = intval($request->get('limit') ?? $votingsOnPage);

        $votings = $this->tokenManager->getVotingByTokenId(
            $token->getId(),
            $offset,
            $limit
        );

        return $this->view($votings, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/list", name="list_voting_crypto", options={"expose"=true})
     * @Rest\QueryParam(name="offset", default=0)
     */
    public function listByCrypto(ParamFetcherInterface $request): View
    {
        /** @var Crypto $crypto */
        $crypto = $this->cryptoManager->findBySymbol(Symbols::WEB);
        $offset = (int)$request->get('offset');

        $votings = $this->cryptoManager->getVotingByCryptoId(
            $crypto->getId(),
            $offset,
            self::VOTINGS_LIST_BATCH_SIZE
        );

        return $this->view($votings, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/vote/{optionId}", name="user_vote", options={"expose"=true}, requirements={"optionId"="\d+"})
     * @throws ApiNotFoundException
     * @throws AccessDeniedHttpException
     * @throws ApiForbiddenException
     */
    public function vote(int $optionId): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var TokenVoting|CryptoVoting|null $voting */
        $voting = $this->votingManager->getByOptionId($optionId);

        if (!$voting) {
            throw new ApiNotFoundException();
        }

        if ($voting->userVoted($user)) {
            throw new ApiForbiddenException($this->translator->trans('voting.already_voted'));
        }

        if ($voting->isCreator($user)) {
            throw new ApiForbiddenException($this->translator->trans('voting.creator_vot_not_allowed'));
        }

        $token = $voting instanceof TokenVoting
            ? $voting->getToken()
            : $voting->getCrypto()
            ;

        $minAmountToVote = $voting instanceof TokenVoting
            ? $this->moneyWrapper->parse(
                $voting->getToken()->getTokenProposalMinAmount(),
                Symbols::TOK
            )
            : $this->votingConfig->getMinBalanceToVote();

        $fullAvailableBalance = $this->balanceHandler->balance(
            $user,
            $token
        )->getFullAvailable();

        $isOwner = $voting instanceof TokenVoting
            ? $voting->getToken()->isOwner($user->getProfile()->getTokens())
            : false;

        if (!$isOwner && $fullAvailableBalance->lessThan($minAmountToVote)) {
            throw new ApiForbiddenException();
        }

        $voting->addUserVoting(
            (new UserVoting())->setUser($user)
                ->setAmount($fullAvailableBalance->getAmount())
                ->setAmountSymbol(
                    $voting instanceof TokenVoting ? Symbols::TOK : $voting->getCrypto()->getSymbol()
                )
                ->setOption($this->optionManager->getByIdFromVoting($optionId, $voting))
        );
        $this->entityManager->persist($voting);
        $this->entityManager->flush();

        if ($voting instanceof TokenVoting) {
            $this->eventDispatcher->dispatch(
                new UserVotingEventActivity($user, $voting, ActivityTypes::USER_VOTED),
                UserVotingEventActivity::NAME
            );
        }

        return $this->view([
            'voting' => $voting,
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/count", name="voting_count", options={"expose"=true})
     * @Rest\QueryParam(name="tokenName", default=null, nullable=true)
     */
    public function votingCount(ParamFetcherInterface $request): View
    {
        $tokenName = $request->get('tokenName');

        if ($tokenName) {
            $token = $this->tokenManager->findByName($tokenName);

            if (!$token) {
                throw new ApiBadRequestException();
            }
        }

        /** @var Token $token */
        $votingsCount = isset($token)
            ? $this->votingManager->countOpenVotingsByToken($token)
            : $this->votingManager->countOpenVotings();

        return $this->view($votingsCount);
    }

    /**
     * @Rest\View()
     * @Rest\Delete("/delete/{id<\d+>}", name="delete_voting", options={"expose"=true})
     * @throws ApiNotFoundException
     * @throws ApiForbiddenException
     */
    public function delete(int $id): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User $user */
        $user = $this->getUser();

        /** @var TokenVoting|null $voting */
        $voting = $this->votingManager->getById($id);

        if (!$voting) {
            throw new ApiNotFoundException($this->translator->trans('voting.not_found'));
        }

        if ($voting->getCreator() !== $user && $voting->getToken()->getOwner() !== $user) {
            throw new ApiForbiddenException();
        }

        $this->entityManager->remove($voting);
        $this->entityManager->flush();

        return $this->view([
            'message' => $this->translator->trans('voting.deleted'),
        ], Response::HTTP_OK);
    }
}
