<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Entity\Voting\CryptoVoting;
use App\Entity\Voting\TokenVoting;
use App\Entity\Voting\UserVoting;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Form\VotingType;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Manager\VotingOptionManagerInterface;
use App\Utils\Converter\SlugConverterInterface;
use App\Utils\Symbols;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        VotingManagerInterface $votingManager,
        VotingOptionManagerInterface $optionManager,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        TranslatorInterface $translator,
        SlugConverterInterface $slugger
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
    public function store(string $tokenName, ParamFetcherInterface $request): View
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Token|Crypto|null $tradable */
        $tradable = $this->cryptoManager->findBySymbol($tokenName)
            ?? $this->tokenManager->findByName($tokenName);

        if (!$tradable) {
            throw new ApiNotFoundException();
        }

        $balance = $this->balanceHandler->balance(
            $user,
            $tradable instanceof Crypto ? Token::getFromCrypto($tradable): $tradable
        )->getAvailable();

        if ((float)$this->moneyWrapper->format($balance) < (float)$this->getParameter('dm_min_amount')) {
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

        $form = $this->createForm(VotingType::class, $voting, ['csrf_protection' => false]);

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

        return $this->view([
            'voting' => $voting,
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/list/{tokenName}", name="list_voting", options={"expose"=true})
     * @param string $tokenName
     * @return View
     * @throws ApiNotFoundException
     */
    public function list(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw new ApiNotFoundException();
        }

        return $this->view($token->getVotings(), Response::HTTP_OK);
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
            : Token::getFromCrypto($voting->getCrypto())
            ;

        $balance = $this->balanceHandler->balance($user, $token)->getAvailable()->getAmount();

        if ((float)$balance <= 0) {
            throw new ApiForbiddenException();
        }

        $voting->addUserVoting(
            (new UserVoting())->setUser($user)
                ->setAmount($balance)
                ->setAmountSymbol(
                    $voting instanceof TokenVoting ? Symbols::TOK : $voting->getCrypto()->getSymbol()
                )
                ->setOption($this->optionManager->getByIdFromVoting($optionId, $voting))
        );
        $this->entityManager->persist($voting);
        $this->entityManager->flush();

        return $this->view([
            'voting' => $voting,
        ], Response::HTTP_OK);
    }
}
