<?php declare(strict_types = 1);

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Events\TokenEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\NotFoundPostException;
use App\Exception\NotFoundTokenException;
use App\Exception\RedirectException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\OrdersFactoryInterface;
use App\Exchange\Factory\TradeInfoFactory;
use App\Exchange\Market\MarketHandlerInterface;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\BlacklistManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Security\Config\DisabledServicesConfig;
use App\Utils\Converter\String\BbcodeMetaTagsStringStrategy;
use App\Utils\Converter\String\DashStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Converter\TokenNameConverterInterface;
use App\Utils\NotificationTypes;
use App\Utils\Verify\WebsiteVerifierInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @Route("/token")
 */
class TokenController extends Controller
{

    protected EntityManagerInterface $em;
    protected ProfileManagerInterface $profileManager;
    protected BlacklistManagerInterface $blacklistManager;
    protected TokenManagerInterface $tokenManager;
    protected CryptoManagerInterface $cryptoManager;
    protected MarketFactoryInterface $marketManager;
    protected TraderInterface $trader;
    private UserActionLogger $userActionLogger;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;
    private BalanceHandlerInterface $balanceHandler;
    private MarketHandlerInterface $marketHandler;
    private ParameterBagInterface $parameterBag;
    private TranslatorInterface $translator;
    private EventDispatcherInterface $eventDispatcher;
    private PostManagerInterface $postManager;
    private TokenNameConverterInterface $tokenNameConverter;
    private AirdropCampaignManagerInterface $airdropCampaignManager;
    private LimitOrderConfig $orderConfig;
    private DisabledServicesConfig $disabledServicesConfig;

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketFactoryInterface $marketManager,
        TraderInterface $trader,
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        BlacklistManager $blacklistManager,
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        BalanceHandlerInterface $balanceHandler,
        MarketHandlerInterface $marketHandler,
        ParameterBagInterface $parameterBag,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        PostManagerInterface $postManager,
        TokenNameConverterInterface $tokenNameConverter,
        AirdropCampaignManagerInterface $airdropCampaignManager,
        LimitOrderConfig $orderConfig,
        DisabledServicesConfig $disabledServicesConfig
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
        $this->trader = $trader;
        $this->userActionLogger = $userActionLogger;
        $this->blacklistManager = $blacklistManager;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->balanceHandler = $balanceHandler;
        $this->marketHandler = $marketHandler;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->postManager = $postManager;
        $this->tokenNameConverter = $tokenNameConverter;
        $this->airdropCampaignManager = $airdropCampaignManager;
        $this->orderConfig = $orderConfig;
        $this->disabledServicesConfig = $disabledServicesConfig;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{name}/donate", name="token_show_donate")
     */
    public function donate(string $name): RedirectResponse
    {
        return $this->redirectToRoute('token_show', [
            'name' => $name,
            'tab' => 'buy',
        ]);
    }

    /**
     * @Route("/{name}/posts/{slug}", name="new_show_post", options={"expose"=true})
     */
    public function showPost(Request $request, string $name, ?string $slug = null): Response
    {
        $token = $this->fetchToken($request, $name);

        $post = $slug
            ? $this->postManager->getBySlug($slug)
            : null;

        if ($slug && !$post) {
            throw new NotFoundPostException();
        }

        if ($post && $post->getToken()->getName() !== $name) {
            throw new NotFoundPostException();
        }

        $tab = $post
            ? 'post'
            : 'posts';

        $extraData = [
            'post' => $this->normalize($post),
            'showEdit' => $this->isGranted('edit', $post) ? 'true' : 'false',
            'comments' => $this->normalize($post->getComments()),
        ];

        return $this->renderPairPage($token, $request, $tab, $extraData);
    }

    /**
     * @Route("/{name}/{tab}/{modal}",
     *     name="token_show",
     *     defaults={"tab" = "intro"},
     *     methods={"GET", "POST"},
     *     requirements={"tab" = "trade|intro|buy", "modal"="settings|created"},
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function show(
        Request $request,
        string $name,
        ?string $tab,
        ?string $modal = null
    ): Response {
        if (preg_match('/(intro)/', $request->getPathInfo()) && !preg_match('/(settings|created)/', $request->getPathInfo())) {
            return $this->redirectToRoute('token_show', ['name' => $name]);
        }

        $token = $this->fetchToken($request, $name);

        return $this->renderPairPage($token, $request, $tab, [
            'showTokenEditModal' => 'settings' === $modal,
            'showCreatedModal' => 'created' === $modal,
        ]);
    }

    /**
     * @Route(name="token_create", options={"expose"=true})
     * @throws ApiBadRequestException
     */
    public function create(
        Request $request,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketStatusManagerInterface $marketStatusManager,
        MailerInterface $mailer,
        OrdersFactoryInterface $ordersFactory
    ): Response {
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken('intro');
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $form->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    throw new ApiBadRequestException($fieldErrors[0]->getMessage());
                }
            }

            throw new ApiBadRequestException('Invalid argument');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->denyAccessUnlessGranted('new-trades');
            $this->denyAccessUnlessGranted('trading');

            if ($this->blacklistManager->isBlacklistedToken($token->getName())) {
                return $this->json(
                    ['blacklisted' => true, 'message' => 'Forbidden token name, please try another'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            /** @var bool $initOrders */
            $initOrders = $form->get('initial_orders')->getData();

            $this->em->beginTransaction();

            /** @var User $user */
            $user = $this->getUser();
            $token->setNextReminderDate(new \DateTime('+1 month'));
            $token->setProfile(
                $this->profileManager->getProfile($this->getUser()) ?? new Profile($user)
            );
            $this->em->persist($token);
            $this->em->flush();

            $mailer->sendKnowledgeBaseMail($user, $token);
            $notificationType = NotificationTypes::TOKEN_MARKETING_TIPS;
            $this->scheduledNotificationManager->createScheduledNotification(
                $notificationType,
                $user,
            );

            try {
                /** @var User $user*/
                $user = $this->getUser();

                $balanceHandler->deposit(
                    $user,
                    $token,
                    $moneyWrapper->parse(
                        (string)$this->getParameter('token_quantity'),
                        MoneyWrapper::TOK_SYMBOL
                    )
                );
                $market = $this->marketManager->createUserRelated($user);

                if ($initOrders) {
                    $ordersFactory->createInitOrders($token);
                }

                $marketStatusManager->createMarketStatus($market);

                $this->em->commit();

                /** @psalm-suppress TooManyArguments */
                $this->eventDispatcher->dispatch(new TokenEvent($token), TokenEvents::CREATED);

                $this->userActionLogger->info('Create a token', ['name' => $token->getName(), 'id' => $token->getId()]);

                return $this->json("success", Response::HTTP_OK);
            } catch (Throwable $exception) {
                if (false !== strpos($exception->getMessage(), 'cURL')) {
                    $this->addFlash('danger', 'Exchanger connection lost. Try again');

                    $this->userActionLogger->error(
                        'Got an error, when registering a token: ',
                        ['message' => $exception->getMessage()]
                    );
                } else {
                    $this->em->rollback();
                    $this->addFlash('danger', 'Error creating token. Try again');

                    $this->userActionLogger->error(
                        'Got an error, when registering a token',
                        ['message' => $exception->getMessage()]
                    );
                }
            }
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => $this->translator->trans('page.token_creation.form_header'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{name}/website-confirmation", name="token_website_confirmation", options={"expose"=true})
     */
    public function getWebsiteConfirmationFile(string $name): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            $token->setWebsiteConfirmationToken(Uuid::uuid1()->toString());
            $this->em->flush();
        }

        $fileContent = WebsiteVerifierInterface::PREFIX . ': ' . $token->getWebsiteConfirmationToken();
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mintme.html'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/show/settings", name="token_show_modal", options={"expose"=true})
     */
    public function showModal(): Response
    {
        return $this->redirectToOwnToken('intro', 'settings');
    }

    private function fetchToken(Request $request, string $name): Token
    {
        $dashedName = (new StringConverter(new DashStringStrategy()))->convert($name);

        if ($dashedName != $name) {
            throw new RedirectException($this->redirectToRoute($request->get('_route'), ['name' => $dashedName]));
        }

        //rebranding
        if (Token::MINTME_SYMBOL === mb_strtoupper($name)) {
            $name = Token::WEB_SYMBOL;
        }

        $token = $this->tokenManager->findByName($name);

        if (!$token || $token->isBlocked()) {
            throw new NotFoundTokenException();
        }

        if ($this->tokenManager->isPredefined($token)) {
            throw new RedirectException(
                $this->redirectToRoute('coin', [
                    'base'=> (Token::WEB_SYMBOL == $token->getName() ? Token::BTC_SYMBOL : $token->getName()),
                    'quote'=> Token::MINTME_SYMBOL,
                ], 301)
            );
        }

        return $token;
    }

    private function redirectToOwnToken(?string $showtab = 'trade', ?string $showTokenEditModal = null): RedirectResponse
    {
        $ownTokens = $this->tokenManager->getOwnTokens();
        $token = $this->tokenManager->getOwnMintmeToken()
            ?? array_pop($ownTokens);

        if (null === $token) {
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        $tokenDashed = (new StringConverter(new DashStringStrategy()))->convert($token->getName());

        return $this->redirectToRoute('token_show', [
            'name' => $tokenDashed,
            'tab' => $showtab,
            'modal' => $showTokenEditModal,
        ]);
    }

    private function isTokenCreated(): bool
    {
        return count($this->tokenManager->getOwnTokens()) > 0;
    }

    private function renderPairPage(Token $token, Request $request, string $tab, array $extraData = []): Response
    {
        $tokenCrypto = $this->cryptoManager->findBySymbol($token->getCryptoSymbol());
        $exchangeCrypto = $this->cryptoManager->findBySymbol($token->getExchangeCryptoSymbol());
        $market = $exchangeCrypto
            ? $this->marketManager->create($exchangeCrypto, $token)
            : null;

        if ($token->getDescription()) {
            $tokenDescription = (new StringConverter(new BbcodeMetaTagsStringStrategy()))->convert($token->getDescription());
            $tokenDescription = preg_replace(
                '/\[\/?(?:b|i|u|s|ul|ol|li|p|s|url|img|h1|h2|h3|h4|h5|h6)*?.*?\]/',
                '\2',
                $tokenDescription
            );
            $tokenDescription = str_replace("\n", " ", $tokenDescription);
            $defaultActivated = true;
        } else {
            $tokenDescription = 'MintMe is a blockchain crowdfunding platform where patrons also earn on their favorite influencer success. Anyone can create a token that represents themselves or their project. When you create a coin, its value represents the success of your project.';
            $defaultActivated = false;
        }

        /** @var  User|null $user */
        $user = $this->getUser();

        $tokenDecimals = $token->getDecimals();

        $tradeInfo = null;

        if ('intro' === $tab) {
            $tradeInfo = (new TradeInfoFactory(
                $market,
                $this->balanceHandler,
                $this->marketHandler,
                $this->parameterBag
            ))->create();
        }

        return $this->render(
            'pages/pair.html.twig',
            array_merge([
                'showSuccessAlert' => $request->isMethod('POST'),
                'token' => $token,
                'tokenCrypto' => $this->normalize($tokenCrypto),
                'tokenDescription' => $tokenDescription,
                'metaTokenDescription' => substr($tokenDescription, 0, 200),
                'showDescription' => $token->isOwner($this->tokenManager->getOwnTokens()) || !$defaultActivated,
                'currency' => $token->getExchangeCryptoSymbol(),
                'hash' => $user ? $user->getHash() : '',
                'profile' => $token->getProfile(),
                'isOwner' => $token->isOwner($this->tokenManager->getOwnTokens()),
                'isTokenCreated' => $this->isTokenCreated(),
                'tab' => $tab,
                'showTrade' => true,
                'showDonation' => true,
                'market' => $this->normalize($market),
                'tokenHiddenName' => $market ?
                    $this->tokenNameConverter->convert($token) :
                    '',
                'precision' => $this->getParameter('token_precision'),
                'isTokenPage' => true,
                'dMMinAmount' => (float)$this->getParameter('dm_min_amount'),
                'showAirdropCampaign' => $token->getActiveAirdrop() ? true : false,
                'userAlreadyClaimed' => $this->airdropCampaignManager
                    ->checkIfUserClaimed($user, $token),
                'posts' => $this->normalize($token->getPosts()),
                'taker_fee' => $this->orderConfig->getTakerFeeRate(),
                'showTokenEditModal' => false,
                'disabledServicesConfig' => $this->normalize($this->disabledServicesConfig),
                'showCreatedModal' => false,
                'tokenSubunit' => null === $tokenDecimals || $tokenDecimals > Token::TOKEN_SUBUNIT
                    ? Token::TOKEN_SUBUNIT
                    : $tokenDecimals,
                'tradeInfo' => $this->normalize($tradeInfo, ['API']),
                'post' => null,
                'showEdit' => 'false',
                'comments' => [],
            ], $extraData)
        );
    }
}
