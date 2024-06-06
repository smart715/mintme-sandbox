<?php declare(strict_types = 1);

namespace App\Controller;

use App\Activity\ActivityTypes;
use App\Communications\DiscordOAuthClientInterface;
use App\Communications\Exception\ApiFetchException;
use App\Config\HideFeaturesConfig;
use App\Config\PostsConfig;
use App\Config\RewardsConfig;
use App\Controller\API\PostsController;
use App\Controller\Traits\ViewOnlyTrait;
use App\Entity\Profile;
use App\Entity\Rewards\Reward;
use App\Entity\Token\Token;
use App\Entity\TokenCrypto;
use App\Entity\User;
use App\Events\Activity\TokenEventActivity;
use App\Events\TokenEvent;
use App\Events\TokenEvents;
use App\Exception\ApiBadRequestException;
use App\Exception\ForbiddenException;
use App\Exception\NotFoundAirdropException;
use App\Exception\NotFoundPostException;
use App\Exception\NotFoundRewardException;
use App\Exception\NotFoundTokenException;
use App\Exception\NotFoundVotingException;
use App\Exception\RedirectException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Token\TokenStatisticsFactoryInterface;
use App\Exchange\Trade\Config\LimitOrderConfig;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\AirdropReferralCodeManagerInterface;
use App\Manager\BlacklistManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\DonationManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\PostManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\RewardManagerInterface;
use App\Manager\ScheduledNotificationManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserTokenFollowManagerInterface;
use App\Manager\VotingManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenPromotionNotificationStrategy;
use App\Security\Config\DisabledBlockchainConfig;
use App\Security\Config\DisabledServicesConfig;
use App\Security\DisabledServicesVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\String\SpaceConverter;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Utils\Validator\AirdropReferralCodeHashValidator;
use App\Utils\Validator\IsCorrectRefererHostValidator;
use App\Utils\Verify\WebsiteVerifierInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Throwable;

/**
 * @Route("/token")
 */
class TokenController extends Controller
{
    public const REWARD_SUMMARY_MODAL = 'reward-summary';
    public const REWARD_FINALIZE_MODAL = 'reward-finalize';
    public const RECENT_POSTS_AMOUNT = 3;
    public const TOKEN_REFERRAL_TYPE = 'invite';
    public const AIRDROP_REFERRAL_TYPE = 'airdrop';

    private const INTRO_TAB = 'intro';
    private const TRADE_TAB = 'trade';
    private const POSTS_TAB = 'posts';
    private const POST_TAB = 'post';
    private const VOTING_TAB = 'voting';
    private const SHOW_VOTING_TAB = 'show-voting';
    private const CREATE_VOTING_TAB = 'create-voting';
    private const DESCRIPTION_TRUNCATE_LENGTH = 70;

    protected EntityManagerInterface $em;
    protected ProfileManagerInterface $profileManager;
    protected BlacklistManagerInterface $blacklistManager;
    protected TokenManagerInterface $tokenManager;
    protected CryptoManagerInterface $cryptoManager;
    protected TraderInterface $trader;
    private UserActionLogger $userActionLogger;
    private ScheduledNotificationManagerInterface $scheduledNotificationManager;
    private BalanceHandlerInterface $balanceHandler;
    private TranslatorInterface $translator;
    private EventDispatcherInterface $eventDispatcher;
    private PostManagerInterface $postManager;
    private VotingManagerInterface $votingManager;
    private AirdropCampaignManagerInterface $airdropCampaignManager;
    private LimitOrderConfig $orderConfig;
    private DisabledServicesConfig $disabledServicesConfig;
    private DisabledBlockchainConfig $disabledBlockchainConfig;
    private MoneyWrapperInterface $moneyWrapper;
    private DiscordOAuthClientInterface $discordOAuthClient;
    private RewardManagerInterface $rewardManager;
    private MarketFactoryInterface $marketFactory;
    private RebrandingConverterInterface $rebrandingConverter;
    private MarketStatusManagerInterface $marketStatusManager;
    private TokenStatisticsFactoryInterface $tokenStatisticsFactory;
    private RewardsConfig $rewardsConfig;
    private HideFeaturesConfig $hideFeaturesConfig;
    protected SessionInterface $session;
    private UserTokenFollowManagerInterface $userTokenFollowManager;
    private PostsConfig $postsConfig;

    public const VOTING_LIST_BATCH_SIZE = 10;
    public const VOTING_LIST_COMPENSATION = 4;

    private bool $topHoldersServiceUnavailable = false; // phpcs:ignore
    private bool $tokenHighestPriceServiceUnavailable = false; // phpcs:ignore
    private DonationManagerInterface $donationManager;

    use ViewOnlyTrait;

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        TraderInterface $trader,
        NormalizerInterface $normalizer,
        UserActionLogger $userActionLogger,
        BlacklistManager $blacklistManager,
        ScheduledNotificationManagerInterface $scheduledNotificationManager,
        BalanceHandlerInterface $balanceHandler,
        TranslatorInterface $translator,
        EventDispatcherInterface $eventDispatcher,
        PostManagerInterface $postManager,
        VotingManagerInterface $votingManager,
        AirdropCampaignManagerInterface $airdropCampaignManager,
        LimitOrderConfig $orderConfig,
        DisabledServicesConfig $disabledServicesConfig,
        DisabledBlockchainConfig $disabledBlockchainConfig,
        MoneyWrapperInterface $moneyWrapper,
        DiscordOAuthClientInterface $discordOAuthClient,
        RewardManagerInterface $rewardManager,
        MarketFactoryInterface $marketFactory,
        RebrandingConverterInterface $rebrandingConverter,
        MarketStatusManagerInterface $marketStatusManager,
        TokenStatisticsFactoryInterface $tokenStatisticsFactory,
        RewardsConfig $rewardsConfig,
        HideFeaturesConfig $hideFeaturesConfig,
        SessionInterface $session,
        UserTokenFollowManagerInterface $userTokenFollowManager,
        PostsConfig $postsConfig,
        DonationManagerInterface $donationManager
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->trader = $trader;
        $this->userActionLogger = $userActionLogger;
        $this->blacklistManager = $blacklistManager;
        $this->scheduledNotificationManager = $scheduledNotificationManager;
        $this->balanceHandler = $balanceHandler;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->postManager = $postManager;
        $this->votingManager = $votingManager;
        $this->airdropCampaignManager = $airdropCampaignManager;
        $this->orderConfig = $orderConfig;
        $this->disabledServicesConfig = $disabledServicesConfig;
        $this->disabledBlockchainConfig = $disabledBlockchainConfig;
        $this->moneyWrapper = $moneyWrapper;
        $this->discordOAuthClient = $discordOAuthClient;
        $this->rewardManager = $rewardManager;
        $this->marketFactory = $marketFactory;
        $this->rebrandingConverter = $rebrandingConverter;
        $this->marketStatusManager = $marketStatusManager;
        $this->tokenStatisticsFactory = $tokenStatisticsFactory;
        $this->rewardsConfig = $rewardsConfig;
        $this->session = $session;
        $this->userTokenFollowManager = $userTokenFollowManager;
        $this->postsConfig = $postsConfig;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->donationManager = $donationManager;

        parent::__construct($normalizer);
    }

    /**
     * @Route("/{userToken}/invite", name="register-referral-by-token", schemes={"https"})
     */
    public function registerReferralByToken(
        string $userToken,
        AuthorizationCheckerInterface $authorizationChecker
    ): Response {
        $token = $this->tokenManager->findByName($userToken);

        if (null === $token) {
            throw new NotFoundHttpException();
        }

        $referralCode = $token->getProfile()->getUser()->getReferralCode();
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('token_show_intro', ['name' => $userToken])
            : $this->redirectToRoute('fos_user_registration_register', ['withReferral' => true]);

        $response->headers->setCookie(new Cookie('referral-code', $referralCode));
        $response->headers->setCookie(new Cookie('referral-type', self::TOKEN_REFERRAL_TYPE));
        $response->headers->setCookie(new Cookie('referral-token', $userToken));

        return $response;
    }

    /**
     * @Route("/{name}/donate", name="token_show_donate")
     */
    public function donate(string $name): RedirectResponse
    {
        return $this->redirectHandle('token_show', ['name' => $name]);
    }

    /**
     * @Route("/{name}/posts/{slug}", name="token_show_post", options={"expose"=true})
     */
    public function showPost(Request $request, string $name, ?string $slug = null): Response
    {
        if ($this->hasSpaces($name)) {
            return $this->redirectHandle('token_show_post', ['name' => $name, 'slug' => $slug]);
        }

        $token = $this->fetchToken($name);

        $post = $slug
            ? $this->postManager->getBySlug($slug)
            : null;

        if ($slug && !$post) {
            return $this->redirectHandle('token_show_intro', ['name' => $name]);
        }

        if ($post && $post->getToken()->getName() !== $token->getName()) {
            throw new NotFoundPostException();
        }

        $tab = $post
            ? self::POST_TAB
            : self::POSTS_TAB;

        $extraData = [
            'post' => $this->normalize($post),
            'comments' => $post ? $this->normalize($post->getComments(), ['API_BASIC']) : null,
        ];

        return $this->renderPairPage($token, $request, $tab, null, null, $extraData);
    }

    /**
     * @Route("/{name}/voting", name="token_list_voting", options={"expose"=true})
     */
    public function listVoting(Request $request, string $name): Response
    {
        if ($this->hasSpaces($name)) {
            return $this->redirectHandle('token_list_voting', ['name' => $name]);
        }

        $token = $this->fetchToken($name);

        return $this->renderPairPage($token, $request, self::VOTING_TAB, null, null, [
            'totalVotingCount' => $this->votingManager->countVotingsByToken($token),
        ]);
    }

    /**
     * @Route("/{name}/create-voting", name="token_create_voting", options={"expose"=true})
     */
    public function createVoting(Request $request, string $name): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('homepage');
        }

        $token = $this->fetchToken($name);

        $fullAvailableBalance = $this->balanceHandler->balance(
            $user,
            $token
        )->getFullAvailable();

        $proposalMinAmount = $this->moneyWrapper->parse(
            $token->getTokenProposalMinAmount(),
            Symbols::TOK
        );

        if ($fullAvailableBalance->lessThan($proposalMinAmount)) {
            throw new ForbiddenException(
                $this->translator->trans('voting.create.min_amount_required', [
                    '%amount%' => (float)$token->getTokenProposalMinAmount(),
                    '%currency%' => $token->getName(),
                ])
            );
        }

        return $this->renderPairPage($token, $request, self::CREATE_VOTING_TAB);
    }

    /**
     * @Route("/{name}/voting/{slug}", name="token_show_voting", options={"expose"=true})
     */
    public function showVoting(Request $request, string $name, string $slug): Response
    {
        if ($this->hasSpaces($name)) {
            return $this->redirectHandle('token_list_voting', ['name' => $name]);
        }

        $token = $this->fetchToken($name);

        $voting = $this->votingManager->getBySlugForTradable($slug, $token);

        if (!$voting) {
            throw new NotFoundVotingException();
        }

        $extraData = [
            'voting' => $this->normalize($voting),
        ];

        return $this->renderPairPage($token, $request, self::SHOW_VOTING_TAB, null, null, $extraData);
    }

    /**
     * @Route("/{name}/sign-up", name="token_sign_up", options={"expose"=true})
     */
    public function signUp(
        AuthorizationCheckerInterface $authorizationChecker,
        string $name
    ): Response {
        $token = $this->fetchToken($name);
        $signUpBonusCode = $token->getSignUpBonusCode();

        $referralCode = $token->getProfile()->getUser()->getReferralCode();
        $response = $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
            ? $this->redirectToRoute('token_show_intro', ['name' => $name])
            : $this->redirectToRoute('fos_user_registration_register', ['withReferral' => true]);

        if ($signUpBonusCode) {
            $response->headers->setCookie(new Cookie('login-bonus', $signUpBonusCode->getCode()));
        }

        $response->headers->setCookie(new Cookie('referral-code', $referralCode));
        $response->headers->setCookie(new Cookie('referral-type', self::TOKEN_REFERRAL_TYPE));
        $response->headers->setCookie(new Cookie('referral-token', $name));

        return $response;
    }

    /**
     * @Route("/{name}/{modal}/{slug}",
     *     name="token_show_intro",
     *     methods={"GET"},
     *     requirements={
     *         "modal" = "created|airdrop|reward-summary|reward-finalize|signup",
     *     },
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function showIntro(
        Request $request,
        string $name,
        ?string $modal = null,
        ?string $slug = null
    ): Response {
        if ($this->hasSpaces($name)) {
            return $this->redirectToRoute(
                'token_show_intro',
                [
                    'name' => (new SpaceConverter())->toDash($name),
                    'modal' => $modal,
                    'slug' => $slug,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        if ('signup' === $modal) {
            return $this->redirectToRoute('token_show_intro', ['name' => $name]);
        }

        $this->checkEnabledFeatures($modal);
        $token = $this->fetchToken($name);

        if (!$this->hideFeaturesConfig->isRewardsEnabled()) {
            return $this->renderPairPage($token, $request, self::INTRO_TAB, $modal);
        }

        $isRewardModal = self::REWARD_SUMMARY_MODAL === $modal ||
            self::REWARD_FINALIZE_MODAL === $modal;
        $reward = null;

        if ($slug && $isRewardModal) {
            $reward = $this->rewardManager->getBySlug($slug);

            if (!$reward || $reward->isFinishedReward()) {
                throw new NotFoundRewardException();
            }

            if (self::REWARD_SUMMARY_MODAL === $modal) {
                $this->denyAccessUnlessGranted('edit', $reward);
            }
        }

        $extraData = [];

        if ($slug && $reward) {
            $extraData['reward'] = $this->normalize($reward, ['API']);
        }

        return $this->renderPairPage(
            $token,
            $request,
            self::INTRO_TAB,
            $modal,
            null,
            $extraData,
        );
    }

    /**
     * @Route("/{name}/{crypto}/trade",
     *     name="token_show_trade",
     *     defaults={"crypto" = "MINTME"},
     *     methods={"GET"},
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function showTrade(
        Request $request,
        string $name,
        string $crypto
    ): Response {
        if ($this->hasSpaces($name)) {
            return $this->redirectToRoute(
                'token_show_trade',
                [
                    'name' => (new SpaceConverter())->toDash($name),
                    'crypto' => $crypto,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        $token = $this->fetchToken($name);
        $marketSymbol = $this->hideFeaturesConfig->isNewMarketsEnabled()
            ? $crypto
            : Symbols::WEB;

        return $this->renderPairPage(
            $token,
            $request,
            self::TRADE_TAB,
            null,
            $marketSymbol,
        );
    }

    /**
     * @Route("/{name}/{crypto}/{tab}/{page}/{modal}/{slug}",
     *     name="token_show",
     *     defaults={"tab" = "intro", "crypto" = "MINTME", "page" = 1},
     *     methods={"GET", "POST"},
     *     requirements={
     *         "page" = "\d+",
     *         "tab" = "trade|intro|buy|posts|voting|create-voting",
     *         "modal" = "settings|signup|created|airdrop|reward-summary|reward-finalize",
     *     },
     *     options={"expose"=true,"2fa_progress"=false}
     * )
     */
    public function show(
        Request $request,
        string $name,
        ?string $crypto,
        ?string $tab,
        ?string $modal = null,
        ?string $slug = null
    ): Response {
        if (self::TRADE_TAB === $tab) {
            return $this->redirectToRoute(
                'token_show_trade',
                [
                    'name' => $name,
                    'crypto' => $crypto,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        if (self::POSTS_TAB === $tab) {
            return $this->redirectToRoute(
                'token_show_post',
                [
                    'name' => $name,
                    'slug' => $slug,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        if (self::VOTING_TAB === $tab) {
            return $this->redirectToRoute(
                'token_list_voting',
                [
                    'name' => $name,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        if (self::CREATE_VOTING_TAB === $tab) {
            return $this->redirectToRoute(
                'token_create_voting',
                [
                    'name' => $name,
                    'saveSuccess' => $request->query->get('saveSuccess'),
                ]
            );
        }

        return $this->redirectToRoute(
            'token_show_intro',
            [
                'name' => $name,
                'modal' => $modal,
                'slug' => $slug,
                'saveSuccess' => $request->query->get('saveSuccess'),
            ]
        );
    }

    private function shouldLoadDiscordRoles(Request $request): bool
    {
        $refererValidator = new IsCorrectRefererHostValidator($request, IsCorrectRefererHostValidator::DISCORD_HOST);

        return $refererValidator->validate();
    }

    private function checkEnabledFeatures(?string $modal): void
    {
        if (!$modal) {
            return;
        }

        if (preg_match('/(reward-summary|reward-finalize)/', $modal) &&
            !$this->hideFeaturesConfig->isRewardsEnabled()
        ) {
            throw new NotFoundHttpException();
        }
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
        MailerInterface $mailer
    ): Response {
        $token = new Token();

        if ($this->isGranted('exceed', $token)) {
            return $this->redirectToOwnToken();
        }

        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $this->isViewOnly()) {
            $this->addFlash('error', 'View only');

            return $this->render('pages/token_creation.html.twig', [
            'formHeader' => $this->translator->trans('page.token_creation.form_header'),
            'form' => $form->createView(),
            'tokenCreateError' => !$this->isGranted('create', $token),
            ]);
        }

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
            $this->denyAccessUnlessGranted(DisabledServicesVoter::NEW_TRADES);
            $this->denyAccessUnlessGranted(DisabledServicesVoter::TRADING);
            $this->denyAccessUnlessGranted('create', $token);

            if ($this->blacklistManager->isBlacklistedToken($token->getName())) {
                return $this->json(
                    ['blacklisted' => true, 'message' => 'Forbidden token name, please try another'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $this->em->beginTransaction();

            /** @var User $user */
            $user = $this->getUser();
            $token->setNextReminderDate(new \DateTimeImmutable('+1 month'));
            $token->setProfile(
                $this->profileManager->getProfile($this->getUser()) ?? new Profile($user)
            );

            $tokenCrypto = new TokenCrypto();
            $tokenCrypto
                ->setCrypto($this->cryptoManager->findBySymbol(Symbols::WEB))
                ->setToken($token);
            $this->em->persist($tokenCrypto);

            $token->addExchangeCrypto($tokenCrypto);

            $this->em->persist($token);
            $this->em->flush();

            $notificationContext = new NotificationContext(new TokenPromotionNotificationStrategy(
                $mailer,
                $token,
            ));
            $notificationContext->sendNotification($user);

            $mailer->sendKnowledgeBaseMail($user, $token);

            $notificationType = NotificationTypes::TOKEN_MARKETING_TIPS;
            $this->scheduledNotificationManager->createScheduledNotification(
                $notificationType,
                $user,
            );

            try {
                /** @var User $user*/
                $user = $this->getUser();

                $this->balanceHandler->beginTransaction();
                $balanceHandler->deposit(
                    $user,
                    $token,
                    $moneyWrapper->parse(
                        (string)$this->getParameter('token_quantity'),
                        Symbols::TOK
                    )
                );
                // So relation UserToken is updated in the token
                // and can get no error in MarketStatus stats
                $this->em->refresh($token);

                $markets = $this->marketFactory->createUserRelated($user);

                $marketStatusManager->createMarketStatus($markets);

                $this->em->commit();
            } catch (Throwable $exception) {
                $this->balanceHandler->rollback();
                $this->em->rollback();

                $this->userActionLogger->error(
                    'Got an error, when registering a token: ',
                    ['message' => $exception->getMessage()]
                );

                throw new ApiFetchException($this->translator->trans('toasted.error.service_unavailable'));
            }

            /** @psalm-suppress TooManyArguments */
            $this->eventDispatcher->dispatch(
                new TokenEventActivity($token, ActivityTypes::TOKEN_CREATED),
                TokenEvents::CREATED
            );
            $this->userActionLogger->info('Create a token', ['name' => $token->getName(), 'id' => $token->getId()]);

            return $this->json("success", Response::HTTP_OK);
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => $this->translator->trans('page.token_creation.form_header'),
            'form' => $form->createView(),
            'tokenCreateError' => !$this->isGranted('create', $token),
        ]);
    }

    /**
     * @Route("/social-media/{name}/website-confirmation", name="token_website_confirmation", options={"expose"=true})
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
     * @Route("/{tokenName}/r/{hash}",
     *     name="airdrop_referral",
     *     options={"expose"=true}
     * )
     */
    public function airdropReferral(
        string $tokenName,
        string $hash,
        AirdropReferralCodeManagerInterface $arcManager
    ): Response {
        $user = $this->getUser();
        $validHash = (new AirdropReferralCodeHashValidator($hash))->validate();

        if ($user) {
            return $this->redirectToRoute('token_show_intro', ['name' => $tokenName, 'modal' => 'airdrop']);
        }

        if (!$validHash) {
            return $this->redirectToRoute('token_show_intro', ['name' => $tokenName]);
        }

        $arc = $arcManager->decode($hash);

        if (!$arc || $arc->getAirdrop()->getToken()->getName() !== $tokenName || !$arc->getAirdrop()->isActive()) {
            return $this->redirectToRoute('token_show_intro', ['name' => $tokenName]);
        }

        $response = $this->redirectToRoute('token_show_intro', ['name' => $tokenName, 'modal' => 'airdrop']);

        $response->headers->setCookie(new Cookie('referral-code', $hash));
        $response->headers->setCookie(new Cookie('referral-type', self::AIRDROP_REFERRAL_TYPE));

        return $response;
    }

    /**
     * @Route("/{name}/airdrop/{airdropId}/embeded",
     *     name="airdrop_embeded",
     *     options={"expose"=true, "2fa_progress"=false},
     *     requirements={"airdropId"="\d+"}
     * )
     */
    public function airdropEmbeded(
        string $name,
        int $airdropId,
        AirdropReferralCodeManagerInterface $arcManager
    ): Response {
        /** @var  User|null $user */
        $user = $this->getUser();

        $token = $this->fetchToken($name);

        $airdrop = $token->getAirdrop($airdropId);
        $referralCode = null;

        if (!$airdrop) {
            throw new NotFoundAirdropException();
        }

        if ($user && $airdrop->getToken()->getOwner()->getId() !== $user->getId()) {
            $referralCode = $arcManager->getByAirdropAndUser($airdrop, $user)
                ?? $arcManager->create($airdrop, $user);
            $referralCode = $arcManager->encode($referralCode);
        }

        $userAlreadyClaimed = $this->airdropCampaignManager->checkIfUserClaimed($user, $token);

        return $this->render('pages/airdrop_embeded.html.twig', [
            'airdrop' => $this->normalize($airdrop, ['API']),
            'referralCode' => $referralCode,
            'token' => $token,
            'isOwner' => $user && $token->isOwner($user->getProfile()->getTokens()),
            'userAlreadyClaimed' => $userAlreadyClaimed,
        ]);
    }

    private function redirectToOwnToken(): RedirectResponse
    {
        $ownTokens = $this->tokenManager->getOwnTokens();
        $token = $this->tokenManager->getOwnMintmeToken()
            ?? array_pop($ownTokens);

        if (null === $token) {
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        $tokenDashed = (new SpaceConverter())->toDash($token->getName());

        return $this->redirectToRoute('token_show_intro', [
            'name' => $tokenDashed,
        ]);
    }

    private function fetchToken(string $name): Token
    {
        $dashedName = (new SpaceConverter())->toDash($name);

        //rebranding
        if (Symbols::MINTME === mb_strtoupper($dashedName)) {
            $dashedName = Symbols::WEB;
        }

        $allCryptos = [
            Symbols::MINTME,
            Symbols::WEB,
            Symbols::ETH,
            Symbols::BNB,
            Symbols::USDC,
            Symbols::BTC,
        ];

        $upper = mb_strtoupper($dashedName);

        if (in_array($upper, $allCryptos)) {
            throw new RedirectException(
                $this->redirectToRoute('coin', [
                    'base'=> in_array($upper, [Symbols::WEB, Symbols::MINTME]) ? Symbols::BTC : $upper,
                    'quote'=> Symbols::MINTME,
                ])
            );
        }

        $token = $this->tokenManager->findByUrl($dashedName);

        if (!$token || $token->isBlocked()) {
            throw new NotFoundTokenException();
        }

        return $token;
    }

    /**
     * @throws NotFoundTokenException
     */
    private function renderPairPage(
        Token $token,
        Request $request,
        string $tab,
        ?string $modal = null,
        ?string $crypto = null,
        array $extraData = []
    ): Response {
        $requestCookies = $request->cookies;
        $isAirdropReferral = $requestCookies->get('referral-code') && self::AIRDROP_REFERRAL_TYPE === $requestCookies->get('referral-type');

        $tokenCrypto = $this->cryptoManager->findBySymbol($token->getCryptoSymbol());
        $markets = $this->marketFactory->createTokenMarkets($token);

        // token always has at least WEB(MINTME) market
        $mintmeMarket = $markets[Symbols::WEB];

        if (!$this->hideFeaturesConfig->isNewMarketsEnabled()) {
            $markets = [Symbols::WEB => $mintmeMarket];
        }

        $exchangeCrypto = $this->cryptoManager->findBySymbol(
            $this->rebrandingConverter->reverseConvert($crypto ?? Symbols::WEB)
        );

        if (!$exchangeCrypto || !$token->containsExchangeCrypto($exchangeCrypto)) {
            throw new NotFoundTokenException();
        }

        $currentMarket = $markets[$exchangeCrypto->getSymbol()];

        if ($token->getDescription()) {
            $tokenDescription = $token->getDescription();
            $tokenDescription = str_replace("\n", " ", $tokenDescription);
            $defaultActivated = false;
        } else {
            $tokenDescription = 'MintMe is a blockchain crowdfunding platform where patrons also earn on their favorite influencer success. Anyone can create a token that represents themselves or their project. When you create a coin, its value represents the success of your project.';
            $defaultActivated = true;
        }

        $truncatedTokenDescription = substr($tokenDescription, 0, self::DESCRIPTION_TRUNCATE_LENGTH) . '...';

        /** @var  User|null $user */
        $user = $this->getUser();

        $rewards = $this->rewardManager->getUnfinishedRewardsByToken($token);

        $tokenDecimals = $token->getDecimals();

        $userAlreadyClaimed = $this->airdropCampaignManager->checkIfUserClaimed($user, $token);

        $topHolders = [];

        try {
            $topHolders = $this->balanceHandler->topHolders(
                $token,
                $this->getParameter('top_holders')
            );
        } catch (\Throwable $e) {
            $this->topHoldersServiceUnavailable = true;
        }

        $discordCallbackUrl = $this->isViewOnly()
            ? $this->getFakeUrl()
            : $this->generateUrl(
                'discord_callback_bot',
                ['_locale' => 'en'],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

        $discordAuthUrl = $this->isViewOnly() ? $this->getFakeUrl() : $this->discordOAuthClient->generateAuthUrl(
            'bot applications.commands',
            $discordCallbackUrl,
            DiscordOAuthClientInterface::BOT_PERMISSIONS_ADMINISTRATOR,
            (string)$token->getId()
        );

        $pageNumber = (int)$request->get('page') ?: 1;

        $posts = 'posts' === $tab
            ? $this->postManager->getActivePostsByToken(
                $token,
                ($pageNumber - 1) * PostsController::POSTS_LIST_BATCH_SIZE,
                PostsController::POSTS_LIST_BATCH_SIZE
            )
            : $this->postManager->getActivePostsByToken($token, 0, 3);

        $tokenPostsAmount = $this->postManager->getActivePostsCountByToken($token);

        try {
            $tokenMarketsHighestPrice = $this->marketStatusManager->getTokenHighestPrice($markets);
        } catch (\Throwable $e) {
            $this->tokenHighestPriceServiceUnavailable = true;
            $this->userActionLogger->error('Error while getting token highest price', [
                'token' => $token->getName(),
                'exception' => $e,
            ]);
            $tokenMarketsHighestPrice = null;
        }

        $tokenStatistics = $this->tokenStatisticsFactory->create($token, $mintmeMarket);
        $directBuyVolume = $this->donationManager->getDirectBuyVolume($token);

        $lockIn = $token->getLockIn();

        $follower = null !== $user && $this->userTokenFollowManager->isFollower($user, $token);

        $amountVoting = count($token->getVotings());

        $votings = $this->getVotings($token, $pageNumber);

        return $this->render(
            'pages/pair.html.twig',
            array_merge([
                'showSuccessAlert' => $request->isMethod(Request::METHOD_POST),
                'token' => $token,
                'normalizedToken' => $this->normalize($token),
                'withdrawn' => $this->normalize($token->getWithdrawn()),
                'tokenCrypto' => $this->normalize($tokenCrypto),
                'lockIn' => $lockIn ? $this->normalize($lockIn) : null,
                'tokenDescription' => $tokenDescription,
                'truncatedTokenDescription' => $truncatedTokenDescription,
                'metaTokenDescription' => substr($tokenDescription, 0, 200),
                'showDescription' => $token->isOwner($this->tokenManager->getOwnTokens()) || !$defaultActivated,
                'hash' => $user ? $user->getHash() : '',
                'profile' => $token->getProfile(),
                'isOwner' => $token->isOwner($this->tokenManager->getOwnTokens()),
                'tab' => $tab,
                'showTrade' => true,
                'mintmeMarket' => $this->normalize($mintmeMarket),
                'currentMarket' => $this->normalize($currentMarket),
                'markets' => $this->normalize($markets),
                'precision' => $this->getParameter('token_precision'),
                'isTokenPage' => true,
                'dMMinAmount' => (float)$token->getDmMinAmount(),
                'showAirdropCampaign' => $token->getActiveAirdrop() ? true : false,
                'userAlreadyClaimed' => $userAlreadyClaimed,
                'posts' => $this->normalize($posts),
                'page' => $pageNumber,
                'posts_amount' => $tokenPostsAmount,
                'recentPostsAmount' => self::RECENT_POSTS_AMOUNT,
                'votings' => $this->normalize($votings, ['API_BASIC']),
                'totalVotingPages' => $this->getTotalVotingPages($amountVoting),
                'taker_fee' => $this->orderConfig->getFeeTokenRate(),
                'showTokenEditModal' => 'settings' === $modal,
                'disabledServicesConfig' => $this->normalize($this->disabledServicesConfig),
                'disabledBlockchain' => $this->disabledBlockchainConfig->getDisabledCryptoSymbols(),
                'showCreatedModal' => 'created' === $modal,
                'showFinalizedRewardModal' => self::REWARD_FINALIZE_MODAL === $modal &&
                    array_key_exists('reward', $extraData),
                'showSummaryRewardModal' => self::REWARD_SUMMARY_MODAL === $modal &&
                    array_key_exists('reward', $extraData),
                'tokenSubunit' => null === $tokenDecimals || $tokenDecimals > Token::TOKEN_SUBUNIT
                    ? Token::TOKEN_SUBUNIT
                    : $tokenDecimals,
                'topHolders' => $this->normalize($topHolders, ['API_BASIC']),
                'showAirdropModal' => !$userAlreadyClaimed && 'airdrop' === $modal,
                'tokenDeleteSoldLimit' => $this->getParameter('token_delete_sold_limit'),
                'post' => null,
                'comments' => [],
                'tokenHighestPriceServiceUnavailable' => $this->tokenHighestPriceServiceUnavailable,
                'topHoldersServiceUnavailable' => $this->topHoldersServiceUnavailable,
                'discordAuthUrl' => $discordAuthUrl,
                'rewards' => $this->normalize($rewards[Reward::TYPE_REWARD], ['API_BASIC']),
                'rewardsMaxLimit' => $this->rewardsConfig->getMaxLimit(Reward::TYPE_REWARD),
                'bounties' => $this->normalize($rewards[Reward::TYPE_BOUNTY], ['API_BASIC']),
                'bountiesMaxLimit' => $this->rewardsConfig->getMaxLimit(Reward::TYPE_BOUNTY),
                'deploys' => $this->normalize($token->getDeploys()),
                'postRewardsCollectableDays' => $this->getParameter('post_rewards_collectable_days'),
                'commentTipCost' => $this->postsConfig->getCommentsTipCost(),
                'commentTipMinAmount' => $this->postsConfig->getCommentsTipMinAmount(),
                'commentTipMaxAmount' => $this->postsConfig->getCommentsTipMaxAmount(),
                'isAuthorizedForReward' => $this->isGranted('collect-reward'),
                'minBalanceToVote' => (float)$token->getTokenProposalMinAmount(),
                'votingProposalMinAmount' => (float)$token->getTokenProposalMinAmount(),
                'commentMinAmount' => (float)$token->getCommentMinAmount(),
                'tokenStatistics' => $this->normalize($tokenStatistics),
                'volumeDonation' => $this->moneyWrapper->format($directBuyVolume),
                'loadDiscordRoles' => $this->shouldLoadDiscordRoles($request),
                'marketsHighestPrice' => $this->normalize($tokenMarketsHighestPrice),
                'ownDeployedTokens' => $this->normalize($this->tokenManager->getOwnDeployedTokens(), ['API_BASIC']),
                'follower' => $follower,
                'enabledCryptos' => $this->normalize($this->cryptoManager->findAll()),
                'isAirdropReferral' => $isAirdropReferral,
            ], $extraData)
        );
    }

    private function getVotings(Token $token, int $pageNumber): array
    {
        $offset = 1 !== $pageNumber
            ? (($pageNumber - 1) * self::VOTING_LIST_BATCH_SIZE) + self::VOTING_LIST_COMPENSATION
            : 0;

        $limit = 1 === $pageNumber
            ? self::VOTING_LIST_BATCH_SIZE + self::VOTING_LIST_COMPENSATION
            : self::VOTING_LIST_BATCH_SIZE;

        return $this->tokenManager->getVotingByTokenId(
            $token->getId(),
            $offset,
            $limit
        );
    }

    private function getTotalVotingPages(int $totalCount): int
    {
        $votingsOnFirstPage = self::VOTING_LIST_BATCH_SIZE + self::VOTING_LIST_COMPENSATION;

        $votingsOnPage = $totalCount <= $votingsOnFirstPage
            ? $votingsOnFirstPage
            : self::VOTING_LIST_BATCH_SIZE;

        return (int)ceil($totalCount / $votingsOnPage);
    }

    private function hasSpaces(string $name): bool
    {
        return (bool)preg_match('/\s/', $name);
    }

    private function redirectHandle(string $routeName, array $params): RedirectResponse
    {
        $params['name'] = (new SpaceConverter())->toDash($params['name']);

        return $this->redirectToRoute($routeName, $params);
    }

    private function getFakeUrl(): string
    {
        return $this->redirectToRoute('homepage')->getTargetUrl();
    }
}
