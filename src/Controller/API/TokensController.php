<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Activity\ActivityTypes;
use App\Communications\ConnectCostFetcherInterface;
use App\Communications\DeployCostFetcherInterface;
use App\Config\HideFeaturesConfig;
use App\Controller\Traits\ViewOnlyTrait;
use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\Token\TokenReleaseAddressHistory;
use App\Entity\TokenInitOrder;
use App\Entity\User;
use App\Events\Activity\SignupBonusActivity;
use App\Events\Activity\TokenEventActivity;
use App\Events\Activity\TokenReleaseActivityEvent;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiForbiddenException;
use App\Exception\ApiNotFoundException;
use App\Exception\ApiUnauthorizedException;
use App\Exception\InvalidAddressException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\ExchangerInterface;
use App\Exchange\Factory\MarketFactoryInterface;
use App\Exchange\Factory\OrdersFactoryInterface;
use App\Exchange\Market\MarketHandlerInterface;
use App\Form\TokenType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\ActivityManagerInterface;
use App\Manager\BlacklistManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\EmailAuthManagerInterface;
use App\Manager\MarketStatusManager;
use App\Manager\TokenCryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TokenSignupBonusCodeManagerInterface;
use App\Repository\TokenInitOrderRepository;
use App\Repository\UserTokenRepository;
use App\Security\Config\DisabledServicesConfig;
use App\Security\DisabledServicesVoter;
use App\Security\UserVoter;
use App\Services\TranslatorService\TranslatorInterface;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\DeploymentFacadeInterface;
use App\Utils\Converter\MarketNameConverterInterface;
use App\Utils\Converter\RebrandingConverterInterface;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Converter\TokenNameConverter;
use App\Utils\LockFactory;
use App\Utils\Symbols;
use App\Utils\Verify\WebsiteVerifier;
use App\Validator\Constraints\TokenReleasePeriod;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Money\Currency;
use Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

/**
 * @Rest\Route("/api/tokens")
 */
class TokensController extends APIController implements TwoFactorAuthenticatedInterface
{
    public const ORDER_REQUEST_LIMIT = 100;
    public const MAX_TOKEN_AMOUNT = 999999.9999;

    private EntityManagerInterface $em;
    protected TokenManagerInterface $tokenManager;
    protected CryptoManagerInterface $cryptoManager;
    private UserActionLogger $userActionLogger;
    protected BlacklistManagerInterface $blacklistManager;
    private TranslatorInterface $translator;
    private MailerInterface $mailer;
    private MarketHandlerInterface $marketHandler;
    private MoneyWrapperInterface $moneyWrapper;
    protected MarketFactoryInterface $marketFactory;
    private OrdersFactoryInterface $ordersFactory;
    private TokenInitOrderRepository $tokenInitOrderRepository;
    private LoggerInterface $logger;
    protected SessionInterface $session;
    private HideFeaturesConfig $hideFeaturesConfig;
    private MarketNameConverterInterface $marketNameConverter;
    private EventDispatcherInterface $eventDispatcher;

    private int $topHolders;
    private int $expirationTime;

    use ViewOnlyTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        UserActionLogger $userActionLogger,
        BlacklistManager $blacklistManager,
        TranslatorInterface $translator,
        MailerInterface $mailer,
        MarketHandlerInterface $marketHandler,
        MoneyWrapperInterface $moneyWrapper,
        MarketFactoryInterface $marketFactory,
        OrdersFactoryInterface $ordersFactory,
        TokenInitOrderRepository $tokenInitOrderRepository,
        HideFeaturesConfig $hideFeaturesConfig,
        LoggerInterface $logger,
        SessionInterface $session,
        MarketNameConverterInterface $marketNameConverter,
        EventDispatcherInterface $eventDispatcher,
        int $topHolders = 10,
        int $expirationTime = 60
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->userActionLogger = $userActionLogger;
        $this->topHolders = $topHolders;
        $this->expirationTime = $expirationTime;
        $this->blacklistManager = $blacklistManager;
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->marketHandler = $marketHandler;
        $this->moneyWrapper = $moneyWrapper;
        $this->marketFactory = $marketFactory;
        $this->ordersFactory = $ordersFactory;
        $this->tokenInitOrderRepository = $tokenInitOrderRepository;
        $this->session = $session;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->logger = $logger;
        $this->marketNameConverter = $marketNameConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update", options={"expose"=true})
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="telegramUrl", nullable=true)
     * @Rest\RequestParam(name="twitterUrl", nullable=true)
     * @Rest\RequestParam(name="discordUrl", nullable=true)
     * @Rest\RequestParam(name="youtubeChannelId", nullable=true)
     * @Rest\RequestParam(name="tokenProposalMinAmount", nullable=true, requirements=@Assert\PositiveOrZero())
     * @Rest\RequestParam(name="dmMinAmount", nullable=true, requirements=@Assert\PositiveOrZero())
     * @Rest\RequestParam(name="commentMinAmount", nullable=true, requirements=@Assert\PositiveOrZero())
     */
    public function update(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $tokenName = $request->get('name');

        if ($tokenName && !$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $this->translator->trans('page.settings_invalid_2fa'));
        }

        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if ($tokenName) {
            $this->validateTokenName($balanceHandler, $token, $tokenName);
        }

        [$socialMediaChanged, $descriptionUpdated] = $this->isSocialMediaAndDescriptionChanged($token, $request);

        $this->handleUpdateForm($token, $request);

        if (null === $token->getDescription() || '' === $token->getDescription()) {
            $token->setNumberOfReminder(0);
            $token->setNextReminderDate(new \DateTimeImmutable('+1 month'));
        }

        $tokenProposalMinAmount = $request->get('tokenProposalMinAmount');

        if (null !== $tokenProposalMinAmount) {
            $this->validateTokenAmount($tokenProposalMinAmount);
            $token->setTokenProposalMinAmount($tokenProposalMinAmount);
        }

        $dmMinAmount = $request->get('dmMinAmount');

        if (null !== $dmMinAmount) {
            $this->validateTokenAmount($dmMinAmount);
            $token->setDmMinAmount($dmMinAmount);
        }

        $commentMinAmount = $request->get('commentMinAmount');

        if (null !== $commentMinAmount) {
            $this->validateTokenAmount($commentMinAmount);
            $token->setCommentMinAmount($commentMinAmount);
        }

        $this->em->persist($token);
        $this->em->flush();

        $this->userActionLogger->info('Change token info', $request->all());

        if ($socialMediaChanged) {
            $this->eventDispatcher->dispatch(
                new TokenEventActivity($token, ActivityTypes::SOCIAL_MEDIA_UPDATED),
                TokenEventActivity::NAME
            );
        }

        if ($descriptionUpdated) {
            $this->eventDispatcher->dispatch(
                new TokenEventActivity($token, ActivityTypes::PROJECT_DESCRIPTION_UPDATED),
                TokenEventActivity::NAME
            );

            return $this->view(
                ['tokenName' => $token->getName(), 'newDescription' => $token->getDescription()],
                Response::HTTP_OK
            );
        }

        return $this->view(['tokenName' => $token->getName()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/website-confirmation", name="token_website_confirm", options={"expose"=true})
     * @Rest\RequestParam(name="url", nullable=true)
     */
    public function confirmWebsite(
        ParamFetcherInterface $request,
        WebsiteVerifier $websiteVerifier,
        string $name
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            return $this->view([
                'verified' => false,
                'errors' => [$this->translator->trans('api.tokens.file_not_downloaded')],
            ], Response::HTTP_OK);
        }

        $url = $request->get('url');

        $isVerified = true;

        if (null != $url) {
            $validator = Validation::createValidator();
            $urlViolations = $validator->validate($url, new Url());

            if (0 < count($urlViolations)) {
                return $this->view([
                    'verified' => false,
                    'errors' => array_map(static function ($violation) {
                        return $violation->getMessage();
                    }, iterator_to_array($urlViolations)),
                ], Response::HTTP_OK);
            }

            $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());
            $message = $this->translator->trans('api.tokens.website_confirmed');
        } else {
            $message = $this->translator->trans('api.tokens.website_deleted');
        }

        if ($isVerified) {
            $token->setWebsiteUrl($url);
            $this->em->flush();
            $this->eventDispatcher->dispatch(
                new TokenEventActivity($token, ActivityTypes::SOCIAL_MEDIA_UPDATED),
                TokenEventActivity::NAME
            );

            $this->userActionLogger->info($message, [
                'token' => $token->getName(),
                'website' => $url,
            ]);
        }

        return $this->view([
            'verified' => $isVerified,
            'errors' => ['fileError' => $websiteVerifier->getError()],
            'message' => $message . ' successfully',
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(
     *     name="released",
     *     allowBlank=false,
     *     requirements="^[2-9][0-9]$|^100$",
     *     nullable=false,
     *     strict=true
     * )
     * @Rest\RequestParam(
     *     name="releasePeriod",
     *     allowBlank=false,
     *     requirements="^[1-4]?[0-9]$|^50$",
     *     nullable=false,
     *     strict=true
     * )
     */
    public function setTokenReleasePeriod(
        string $name,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        ValidatorInterface $validator
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $token = $this->tokenManager->findByName($name);

        if (!$token || !$token->isCreatedOnMintmeSite()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException('Token is deploying or deployed.');
        }

        $released = $request->get('released');
        $constraint = new TokenReleasePeriod([
            "validReleasePeriod" => "/^(0|[1-3]|5|1[0,5]|2[0]|[3,4,5]0)$/",
            "minReleasePeriod" => 1,
            "maxReleasePeriod" => 50,
            "fullReleasePeriod" => 0,
            "minTokenReleased" => 20,
            "maxTokenReleased" => 99,
            "fullTokenReleased" => 100,
        ]);

        $violations = $validator->validate([$released, $request->get('releasePeriod')], $constraint);

        if (0 < count($violations)) {
            throw array_reduce(
                iterator_to_array($violations),
                fn ($carry, $violation): ApiBadRequestException => new ApiBadRequestException(
                    (string)$violation->getMessage(),
                    0,
                    $carry,
                ),
                new ApiBadRequestException()
            );
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $lock = $token->getLockIn() ?? new LockIn($token);
        $isNotExchanged = $balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'));

        $form = $this->createFormBuilder($lock, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
            'validation_groups' => ['Default', !$isNotExchanged ? 'Exchanged' : ''],
        ])
            ->add('releasePeriod')
            ->getForm();

        $form->submit($request->all());

        if (!$form->isValid()) {
            return $this->view($form);
        }

        if (!$lock->getId() || $isNotExchanged) {
            /** @var  User $user*/
            $user = $this->getUser();
            $balance = $balanceHandler->balance($user, $token);

            if ($balance->isFailed()) {
                return $this->view(
                    $this->translator->trans('toasted.error.service_unavailable'),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $releasedAmount = $balance->getAvailable()->divide(100)->multiply($released);
            $tokenQuantity = $this->moneyWrapper->parse(
                (string)$this->getParameter('token_quantity'),
                Symbols::TOK
            );
            $amountToRelease = $balance->getAvailable()->subtract($releasedAmount);

            $lock->setAmountToRelease($amountToRelease)
                ->setReleasedAtStart($tokenQuantity->subtract($amountToRelease)->getAmount());
        }

        $this->em->persist($lock);
        $this->em->flush();

        $this->eventDispatcher->dispatch(
            new TokenReleaseActivityEvent($lock, $token),
            TokenReleaseActivityEvent::NAME
        );

        $this->userActionLogger->info('Set token release period', ['period' => $lock->getReleasePeriod()]);

        return $this->view($lock);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/lock-period", name="lock-period", options={"expose"=true})
     */
    public function lockPeriod(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view($token->getLockIn());
    }

    /**
     * @Rest\View()
     * @Rest\Get("/search", name="token_search", options={"expose"=true})
     * @Rest\QueryParam(name="tokenName", allowBlank=false)
     */
    public function tokenSearch(ParamFetcherInterface $request): View
    {
        return $this->view($this->tokenManager->getTokensByPattern(
            $request->get('tokenName')
        ));
    }

    /**
     * @Rest\View()
     * @Rest\Get(name="tokens", options={"expose"=true})
     * @Rest\QueryParam(name="tokensInfo", nullable=true)
     * @Rest\QueryParam(name="page", nullable=true)
     * @return View
     * @param ParamFetcherInterface $request
     */
    public function getTokens(
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactoryInterface $viewFactory,
        ParamFetcherInterface $request,
        UserTokenRepository $userTokenRepository
    ): View {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user*/
        $user = $this->getUser();

        $page = $request->get('page')
            ? (int)$request->get('page')
            : null;

        $tokens = $userTokenRepository->findByUserNonReferralTokens(
            $user->getId(),
            $page,
            $this->getParameter('user_tokens_per_page')
        );

        $cryptos = $this->cryptoManager->findAllAssets();

        try {
            $common = $balanceHandler->balances(
                $user,
                $tokens
            );
        } catch (BalanceException $exception) {
            if (BalanceException::EMPTY == $exception->getCode()) {
                $common = BalanceResultContainer::fail();
            } else {
                return $this->view(null, 500);
            }
        }

        $predefined = $balanceHandler->balances(
            $user,
            $cryptos
        );

        $tokensInfoView = $request->get('tokensInfo')
            ? $this->indexTokensBySymbol($user->getTokens())
            : null;
        $commonView = $viewFactory->create($tokens, $common, $user);
        $predefinedView = $viewFactory->create($cryptos, $predefined, $user);

        return $this->view([
            'tokensInfo' => $tokensInfoView,
            'common' => $commonView,
            'predefined' => $predefinedView,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/ping", name="tokens_ping", options={"expose"=true})
     */
    public function getTokensPing(BalanceHandlerInterface $balanceHandler): View
    {
        $serviceAvailable = $balanceHandler->isServiceAvailable();

        return $serviceAvailable
            ? $this->view('pong', Response::HTTP_OK)
            : $this->view(null, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/balance", name="token_balance", options={"expose"=true})
     * @return View
     */
    public function getTokenBalance(
        string $name,
        BalanceHandlerInterface $balanceHandler,
        BalanceViewFactoryInterface $viewFactory
    ): View {
        $user = $this->getUser();
        $token = $this->tokenManager->findByName($name);

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user*/
        $user = $this->getUser();

        try {
            $balanceResult = $balanceHandler->balance(
                $user,
                $token
            );
            $realBalance = $this->tokenManager->getRealBalance(
                $token,
                $balanceResult,
                $user
            );
        } catch (BalanceException $exception) {
            if (BalanceException::EMPTY == $exception->getCode()) {
                $realBalance = BalanceResultContainer::fail();
            } else {
                return $this->view(null, 500);
            }
        }

        return $this->view($realBalance);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/exchange-amount", name="token_exchange_amount", options={"expose"=true})
     */
    public function getTokenExchange(string $name, BalanceHandlerInterface $balanceHandler): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $balance = $balanceHandler->exchangeBalance(
            $token->getProfile()->getUser(),
            $token
        );

        return $this->view($balance);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/withdrawn", name="token_withdrawn", options={"expose"=true})
     */
    public function getTokenWithdrawn(Token $token): View
    {
        $withdrawn = Token::DEPLOYED === $token->getDeploymentStatus()
            ? $token->getWithdrawn()
            : new Money(0, new Currency(Symbols::TOK));

        return $this->view($withdrawn);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/is-exchanged", name="is_token_exchanged", options={"expose"=true})
     */
    public function isTokenExchanged(string $name, BalanceHandlerInterface $balanceHandler): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view(
            !$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/deployment-status", name="token_deployment_status", options={"expose"=true})
     */
    public function getDeploymentStatus(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view([
            'status' => $token->getDeploymentStatus(),
            'crypto' => $token->getCrypto(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/deploys", name="token_deploys", options={"expose"=true})
     */
    public function getDeploys(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view($token->getDeploys(), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/networks", name="get_token_networks", options={"expose"=true})
     */
    public function getTokenNetworks(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view($this->tokenManager->getTokenNetworks($token), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/delete", name="token_delete", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function delete(
        ParamFetcherInterface $request,
        EmailAuthManagerInterface $emailAuthManager,
        ExchangerInterface $exchanger,
        string $name
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        if (!$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $this->translator->trans('page.settings_invalid_2fa'));
        }

        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (!$token || !$token->isCreatedOnMintmeSite()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('delete', $token);

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException(
                $this->translator->trans('token.delete.body.deploying_or_deployed')
            );
        }

        if ($this->isTokenOverDeleteLimit($token)) {
            $limit = (string)$this->getParameter('token_delete_sold_limit');

            throw new ApiBadRequestException(
                $this->translator->trans('token.delete.body.over_limit', ['%limit%' => $limit])
            );
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $response = $emailAuthManager->checkCode($user, $request->get('code'));

            if (!$response->getResult()) {
                throw new ApiUnauthorizedException($response->getMessage());
            }

            $user->setEmailAuthCode('');
            $this->em->persist($user);
        }

        $markets = $this->marketFactory->createTokenMarkets($token);

        foreach ($markets as $market) {
            $offset = 0;

            while ($pendingBuyOrders = $this->marketHandler->getPendingBuyOrders(
                $market,
                $offset,
                self::ORDER_REQUEST_LIMIT
            )) {
                foreach ($pendingBuyOrders as $order) {
                    $exchanger->cancelOrder($market, $order);
                }

                $offset += self::ORDER_REQUEST_LIMIT;
            }
        }

        $this->deleteTokenInitialOrders($name);

        $this->em->remove($token);
        $this->em->flush();

        $this->addFlash(
            'success',
            $this->translator->trans('api.tokens.deleted', ['%tokeName%' => $token->getName()])
        );

        $this->mailer->sendTokenDeletedMail($token);

        $this->userActionLogger->info(
            "Delete token",
            ['name' =>  $token->getName(), 'code' => $request->get('code')]
        );

        return $this->view(
            ['message' => $this->translator->trans('api.tokens.delete_successfull')],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/over-delete-limit", name="token_over_delete_limit", options={"expose"=true})
     */
    public function overDeleteLimit(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        return $this->view($this->isTokenOverDeleteLimit($token), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/send-code", name="token_send_code", options={"expose"=true})
     */
    public function sendCode(EmailAuthManagerInterface $emailAuthManager, string $name): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        /** @var User $user*/
        $user = $this->getUser();
        $message = null;

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $emailAuthManager->generateCode($user, $this->expirationTime);
            $this->mailer->sendAuthCodeToMail(
                $this->translator->trans('api.tokens.confirm_email_header'),
                $this->translator->trans('api.tokens.confirm_email_body'),
                $user
            );
            $message = $this->translator->trans('api.tokens.confirm_email_sent');
        }

        return $this->view(['message' => $message], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/top-holders", name="top_holders", options={"expose"=true})
     */
    public function getTopHolders(
        string $name,
        BalanceHandlerInterface $balanceHandler
    ): View {
        $tradable = $this->cryptoManager->findBySymbol($name) ??
            $this->tokenManager->findByName($name);

        if (null === $tradable) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.not_found'));
        }

        $topTraders = $balanceHandler->topHolders(
            $tradable,
            $this->topHolders,
        );

        return $this->view($topTraders, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/deploy-costs", name="token_deploy_costs", options={"expose"=true})
     */
    public function deployCosts(DeployCostFetcherInterface $costFetcher): View
    {
        try {
            return $this->view($costFetcher->getCosts(), Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching deploy costs: ' . $e->getMessage());

            return $this->view([
                'error' => $this->translator->trans('toasted.error.external'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/connect-costs",
     *     name="token_connect_costs",
     *     options={"expose"=true},
     *     condition="%feature_token_connect_enabled%"
     * )
     */
    public function connectCosts(ConnectCostFetcherInterface $costFetcher): View
    {
        return $this->view($costFetcher->getCosts(), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/deploy", name="token_deploy", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(name="currency", allowBlank=false)
     * @param string $name
     * @param DeploymentFacadeInterface $deployment
     * @return View
     * @throws ApiUnauthorizedException|ApiForbiddenException|ApiBadRequestException|ApiNotFoundException
     */
    public function deploy(
        string $name,
        ParamFetcherInterface $request,
        DeploymentFacadeInterface $deployment,
        RebrandingConverterInterface $rebrandingConverter,
        DisabledServicesConfig $disabledServicesConfig
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->denyAccessUnlessGranted(DisabledServicesVoter::DEPLOY);

        $token = $this->tokenManager->findByName($name);

        if (!$token || !$token->isCreatedOnMintmeSite()) {
            throw new ApiNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (!$token->getLockIn()) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.has_not_releaded_period'));
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unauthorized'));
        }

        $crypto = $request->get('currency');
        $rebrandedCrypto = $rebrandingConverter->convert($crypto);

        if (!$disabledServicesConfig->getBlockchainDeployStatusByCrypto($rebrandedCrypto)) {
            throw new ApiBadRequestException($this->translator->trans(
                'api.tokens.disabled_blockchain_deploy',
                ['%blockchain%' => $rebrandedCrypto]
            ));
        }

        $crypto = $this->cryptoManager->findBySymbol($crypto);

        if (!$crypto->isBlockchainAvailable()) {
            throw new ApiBadRequestException(
                $this->translator->trans('blockchain_unavailable', [
                    'blockchainName' => $this->cryptoManager->getNetworkName($crypto->getSymbol()),
                ])
            );
        }

        if (!$crypto) {
            throw new ApiBadRequestException();
        }

        $deploys = $token->getDeploys();

        foreach ($deploys as $deploy) {
            if (!$this->hideFeaturesConfig->isTokenConnectEnabled() && !$deploy->isPending()) {
                throw new ApiBadRequestException($this->translator->trans('api.tokens.internal_error'));
            }

            if ($crypto->getId() === $deploy->getCrypto()->getId() ||
                $deploy->isPending()
            ) {
                throw new ApiBadRequestException($this->translator->trans('api.tokens.deploying'));
            }
        }

        try {
            /** @var User $user*/
            $user = $this->getUser();

            $deployment->execute($user, $token, $crypto);
        } catch (Throwable $ex) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.internal_error'));
        }

        $this->userActionLogger->info('Deploy Token', [
            'name' => $name,
            'crypto' => $crypto->getSymbol(),
        ]);

        return $this->view();
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{crypto}/contract/fee", name="token_contract_fee", options={"expose"=true})
     * @return View
     */
    public function contractMethodFee(
        string $crypto,
        ContractHandlerInterface $contractHandler
    ): View {
        $cryptoInstance = $this->cryptoManager->findBySymbol($crypto);

        if (!$cryptoInstance) {
            throw new ApiNotFoundException();
        }

        return $this->view(
            $contractHandler->getContractMethodFee($cryptoInstance->getSymbol()),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/contract/update", name="token_contract_update", options={"expose"=true})
     * @Rest\RequestParam(name="address", allowBlank=false)
     * @Rest\RequestParam(name="code", nullable=true)
     * @param string $name
     * @param ParamFetcherInterface $request
     * @param ContractHandlerInterface $contractHandler
     * @return View
     * @throws ApiBadRequestException
     * @throws ApiNotFoundException
     * @throws ApiUnauthorizedException
     */
    public function contractUpdate(
        string $name,
        ParamFetcherInterface $request,
        ContractHandlerInterface $contractHandler,
        LockFactory $lockFactory,
        BalanceHandlerInterface $balanceHandler
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        if (!$this->isGranted('2fa-login', $request->get('code'))) {
            throw new UnauthorizedHttpException('2fa', $this->translator->trans('page.settings_invalid_2fa'));
        }

        $token = $this->tokenManager->findByName($name);

        if (null === $token || !$token->isCreatedOnMintmeSite()) {
            throw new ApiNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unauthorized'));
        }

        /** @var User $user */
        $user = $this->getUser();

        $lock = $lockFactory->createLock(LockFactory::LOCK_BALANCE . $user->getId());

        if (!$lock->acquire()) {
            throw $this->createAccessDeniedException();
        }

        $fee = null;

        try {
            $oldAddress = $token->getMintDestination();
            $newAddress = $request->get('address');

            if (!$this->validateEthereumAddress($newAddress)) {
                throw new InvalidAddressException('Invalid Ethereum address');
            }

            $crypto = $token->getCrypto();
            $fee = $contractHandler->getContractMethodFee($crypto->getSymbol());

            $balance = $balanceHandler->balance($user, $crypto);

            if ($balance->getAvailable()->lessThan($fee)) {
                throw new ApiBadRequestException($this->translator->trans('quick_trade.insufficient_balance'));
            }

            $balanceHandler->withdraw($user, $crypto, $fee);

            $contractHandler->updateMintDestination($token, $newAddress);

            $tokenReleaseAdressHistory = new TokenReleaseAddressHistory(
                $user,
                $token,
                $token->getMainDeploy()->getCrypto(),
                $fee,
                $oldAddress,
                $newAddress
            );

            $token->setUpdatingMintDestination();

            $this->em->persist($token);
            $this->em->persist($tokenReleaseAdressHistory);
            $this->em->flush();
        } catch (Throwable $ex) {
            if ($ex instanceof InvalidAddressException) {
                throw new ApiBadRequestException('Invalid Address');
            } else {
                $this->userActionLogger->error('Error while updating token mintDestination', [
                    'message' => $ex->getMessage(),
                ]);

                throw new ApiBadRequestException($this->translator->trans('api.tokens.internal_error'));
            }
        } finally {
            $lock->release();
        }

        $this->userActionLogger->info('Update token mintDestination', ['name' => $name]);

        return $this->view(['fee' => $fee], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/sold", name="token_sold_on_market", options={"expose"=true})
     * @param string $name
     * @return View
     * @throws ApiNotFoundException
     */
    public function soldOnMarket(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        return $this->view($this->marketHandler->soldOnMarket($token), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/check-token-name-exists", name="check_token_name_exists", options={"expose"=true})
     * @param string $name
     * @return View
     */
    public function checkTokenNameExists(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        return $this->view(['exists' => null !== $token], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/is-unique-token-name", name="is_unique_token_name", options={"expose"=true})
     * @param string $name
     * @return View
     */
    public function isUniqueTokenName(string $name): View
    {
        /** @var User $user */
        $user = $this->getUser();
        $token = $this->tokenManager->findByName($name);

        return $this->view([
            'exists' => null !== $token && $token->getProfile()->getUser()->getId() !== $user->getId(),
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/tokens-creation", name="check_token_creation", options={"expose"=true})
     * @return View
     */
    public function isTokenCreationEnabled(): View
    {
        return $this->view([
            'tokenCreation' => $this->isGranted(DisabledServicesVoter::NEW_TRADES)
                && $this->isGranted(DisabledServicesVoter::TRADING)
                && $this->isGranted(UserVoter::NOT_BLOCKED),
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/token-name-blacklist-check", name="token_name_blacklist_check", options={"expose"=true})
     * @param string $name
     * @return View
     */
    public function checkTokenNameBlacklistAction(string $name): View
    {
        return $this->view(
            ['blacklisted' => $this->blacklistManager->isBlacklistedToken($name)],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/update/deployed-modal/{tokenName}", name="token_update_deployed_modal", options={"expose"=true})
     * @param string $tokenName
     * @return View
     */
    public function updateShowDeployedModal(string $tokenName): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $userToken = $this->tokenManager->getOwnTokenByName($tokenName);

        if (!$userToken || !$userToken->isCreatedOnMintmeSite()) {
            throw new AccessDeniedException();
        }

        $userToken->setShowDeployedModal(false);

        $this->em->persist($userToken);
        $this->em->flush();

        return $this->view([], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/create",
     *     name="token_crypto_create",
     *     options={"expose"=true},
     *     condition="%feature_create_new_markets_enabled%"
     * )
     * @Rest\RequestParam(name="marketCrypto", allowBlank=false)
     * @Rest\RequestParam(name="payCrypto", allowBlank=false)
     * @Rest\RequestParam(name="tokenName", allowBlank=false)
     * @throws ApiBadRequestException
     */
    public function marketCreate(ParamFetcherInterface $request, TokenCryptoManagerInterface $tokenCryptoManager): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            $this->createAccessDeniedException();
        }

        $token = $this->tokenManager->findByName($request->get('tokenName'));
        $marketCrypto = $this->cryptoManager->findBySymbol($request->get('marketCrypto'));
        $payCrypto = $this->cryptoManager->findBySymbol($request->get('payCrypto'));

        if (!$marketCrypto ||
            !$payCrypto ||
            !$token ||
            (!$this->hideFeaturesConfig->isCryptoEnabled($marketCrypto->getSymbol()))
        ) {
            throw new ApiBadRequestException();
        }

        $this->denyAccessUnlessGranted('edit', $token);
        $this->denyAccessUnlessGranted('create', $this->marketFactory->create($marketCrypto, $token));

        $tokenCryptoManager->createTokenCrypto($payCrypto, $marketCrypto, $token);

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/token-initial-orders", name="token_initial_orders", options={"expose"=true})
     * @Rest\RequestParam(name="initTokenPrice", allowBlank=false)
     * @Rest\RequestParam(name="priceGrowth", allowBlank=false)
     * @Rest\RequestParam(name="tokensForSale", allowBlank=false)
     * @Rest\RequestParam(name="tokenName", allowBlank=false)
     * @throws ApiBadRequestException
     */
    public function placeTokenInitialOrders(
        ParamFetcherInterface $request
    ): View {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->em->beginTransaction();

        try {
            /** @var User|null $user */
            $user = $this->getUser();

            if (!$user) {
                $this->createAccessDeniedException();
            }

            $token = $this->tokenManager->findByName($request->get('tokenName'));

            if (!$token) {
                throw new ApiBadRequestException();
            }

            $this->denyAccessUnlessGranted('edit', $token);

            $this->ordersFactory->createInitOrders(
                $token,
                $request->get('initTokenPrice'),
                $request->get('priceGrowth'),
                $request->get('tokensForSale')
            );

            $this->em->commit();

            return $this->view(null, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('api.tokens.error_creating_orders'));
            $this->em->rollback();

            throw $e;
        }
    }

    /**
     * @Rest\View()
     * @Rest\Post("/token-initial-orders-delete/{tokenName}", name="delete_token_initial_orders", options={"expose"=true})
     */
    public function deleteTokenInitialOrders(string $tokenName): View
    {
        if ($this->isViewOnly()) {
            throw new ApiForbiddenException('View only');
        }

        $this->em->beginTransaction();

        try {
            /** @var User|null $user */
            $user = $this->getUser();

            if (!$user) {
                $this->createAccessDeniedException();
            }

            $token = $this->tokenManager->findByName($tokenName);

            if (!$token) {
                throw new ApiBadRequestException();
            }

            $initialOrders = $this->em->getRepository(TokenInitOrder::class)->findBy([
                'user' => $user,
                'marketName' => $this->getMarketNameForToken($token),
            ]);

            foreach ($initialOrders as $order) {
                $this->ordersFactory->removeTokenInitOrders($user, $token, $order);
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', $this->translator->trans('api.tokens.error_deleting_orders'));
            $this->em->rollback();

            return $this->view(null, Response::HTTP_BAD_REQUEST);
        }

        $this->em->commit();

        return $this->view(null, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/check-initial-orders/{tokenName}", name="check_initial_orders", options={"expose"=true})
     */
    public function checkIfUserHasInitialOrders(string $tokenName): bool
    {
        /** @var User $user */
        $user = $this->getUser();

        $token = $this->tokenManager->findByName($tokenName);

        return (bool)$this->tokenInitOrderRepository->findOneBy([
            'user' => $user,
            'marketName' => $this->getMarketNameForToken($token),
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/has-token-sign-up-bonus-link/{tokenName}",
     *     name="has_active_token_signup_bonus",
     *     options={"expose"=true}
     * )
     */
    public function hasActiveTokenSignupBonus(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $signUpBonusCode = $token->getSignUpBonusCode();

        if (!$signUpBonusCode) {
            return $this->view(null, Response::HTTP_NO_CONTENT);
        }

        return $this->view(null, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/create-token-sign-up-bonus-link/{tokenName}",
     *     name="create_token_sign_up_bonus_link",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(name="amount", nullable=false)
     * @Rest\RequestParam(name="participants", nullable=false)
     */
    public function createTokenSignupBonusLink(
        string $tokenName,
        ParamFetcherInterface $request,
        TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager,
        LockFactory $lockFactory
    ): View {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $userId = $token->getOwner()->getId();
        $lock = $lockFactory->createLock(LockFactory::LOCK_BALANCE . $userId);

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        $amount = (string)$request->get('amount');
        $participants = (string)$request->get('participants');

        // Validate amount and participants
        $this->validateProperties([$participants], [$amount]);

        try {
            $tokenSignupBonus = $tokenSignUpBonusCodeManager->createTokenSignupBonusLink(
                $token,
                $this->moneyWrapper->parse($amount, Symbols::TOK),
                (int)$participants
            );
            $this->eventDispatcher->dispatch(
                new SignupBonusActivity($tokenSignupBonus, ActivityTypes::SIGN_UP_BONUS_CREATED),
                SignupBonusActivity::NAME
            );
        } catch (\Throwable $e) {
            $this->logger->error('Error creating token signup bonus link: ' . $e->getMessage());

            return $this->view(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } finally {
            $lock->release();
        }

        return $this->view(null, Response::HTTP_CREATED);
    }

    /**
     * @Rest\View()
     * @Rest\Delete(
     *     "/delete-token-sign-up-bonus-link/{tokenName}",
     *     name="delete_token_sign_up_bonus_link",
     *     options={"expose"=true}
     * )
     */
    public function deleteTokenSignupBonusLink(
        string $tokenName,
        TokenSignupBonusCodeManagerInterface $tokenSignUpBonusCodeManager
    ): View {
        $token = $this->tokenManager->getOwnTokenByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $tokenSignUpBonusCodeManager->deleteTokenSignupBonusLink($token);

        return $this->view(null, Response::HTTP_OK);
    }

    /**
     * @Rest\View(serializerGroups={"API", "EXTENDED_INFO"})
     * @Rest\Get("/top/{limit}", name="top_tokens", options={"expose"=true})
     */
    public function topTokens(
        int $limit,
        ActivityManagerInterface $activityManager,
        MarketStatusManager $marketStatusManager,
        RebrandingConverterInterface $rebrandingConverter
    ): View {
        $topTokens = $activityManager->getLastByTypes(
            [ActivityTypes::TOKEN_TRADED, ActivityTypes::DONATION],
            $limit
        );
        $markets = [];

        foreach ($topTokens as $token) {
            if (null === $token['fullTokenName']) {
                continue;
            }

            $markets[] = $marketStatusManager->findByBaseQuoteNames(
                $rebrandingConverter->reverseConvert($token['symbol']),
                $token['fullTokenName']
            );
        }

        return $this->view($marketStatusManager->convertMarketStatusKeys($markets), Response::HTTP_OK);
    }

    /**
     * @Rest\Get("/metadata/{tokenId}", name="token_metadata", options={"expose"=true})
     */
    public function tokenMetadata(
        string $tokenId,
        TokenNameConverter $tokenNameConverter,
        CacheManager $imageCacheManager
    ): Response {
        $tokenId = $tokenNameConverter->parseConvertedId($tokenId);
        $token = $this->tokenManager->findById($tokenId);

        if (!$token) {
            throw $this->createNotFoundException();
        }

        $response = new Response(json_encode([
            "name" => $token->getName(),
            "description" => $token->getDescription(),
            "image" => $imageCacheManager->generateUrl($token->getImage()->getUrl(), 'avatar_small'),
        ], JSON_UNESCAPED_SLASHES), Response::HTTP_OK);
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }

    private function isTokenOverDeleteLimit(Token $token): bool
    {
        $soldOnMarket = $this->marketHandler->soldOnMarket($token);

        $saleLimit = $this->moneyWrapper->parse(
            (string)$this->getParameter('token_delete_sold_limit'),
            Symbols::TOK
        );

        return $soldOnMarket->greaterThanOrEqual($saleLimit);
    }

    private function validateEthereumAddress(string $address): bool
    {
        return 0 === strpos($address, '0x') && (42 === strlen($address));
    }

    private function handleUpdateForm(Token $token, ParamFetcherInterface $request): void
    {
        $form = $this->createForm(TokenType::class, $token, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $form->submit(array_filter($request->all(), function ($value) {
            return null !== $value;
        }), false);

        if (!$form->isValid()) {
            foreach ($form->all() as $childForm) {
                /** @var FormError[] $fieldErrors */
                $fieldErrors = $form->get($childForm->getName())->getErrors();

                if (count($fieldErrors) > 0) {
                    throw new ApiBadRequestException($fieldErrors[0]->getMessage());
                }
            }

            throw new ApiBadRequestException($this->translator->trans('api.tokens.invalid_argument'));
        }
    }

    private function indexTokensBySymbol(array $tokens): array
    {
        return array_reduce($tokens, function (array $acc, Token $token) {
            $acc[$token->getName()] = $token;

            return $acc;
        }, []);
    }

    private function validateTokenName(
        BalanceHandlerInterface $balanceHandler,
        Token $token,
        string $tokenName
    ): void {
        if (!$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.you_need_all_tokens_to_change_name'));
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.deploying'));
        }

        if ($this->blacklistManager->isBlacklistedToken($tokenName)) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.forbidden_name'));
        }
    }

    private function getMarketNameForToken(Token $token): String
    {
        $market = $this->marketFactory->create(
            $this->cryptoManager->findBySymbol(Symbols::WEB),
            $token
        );

        return $this->marketNameConverter->convert($market);
    }

    private function validateTokenAmount(string $amount): void
    {
        $tokenAmount = ltrim($amount, '0') ?: '0';
        $amountDecimals = (int)strpos(strrev($tokenAmount), '.');
        $tokenPrecision = Token::TOKEN_SUBUNIT;

        if (!is_numeric($tokenAmount)
            || $tokenAmount < 0
            || $tokenAmount > self::MAX_TOKEN_AMOUNT) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.invalid_argument'));
        }

        if ($amountDecimals > $tokenPrecision) {
            throw new ApiBadRequestException($this->translator->trans(
                'api.tokens.amount.max_decimals',
                ['%maxDecimals%' => $tokenPrecision]
            ));
        }
    }

    /** @return  array{0: bool, 1: bool} */
    private function isSocialMediaAndDescriptionChanged(Token $token, ParamFetcherInterface $request): array
    {
        $socialMediaChanged = false;
        $descriptionChanged = false;

        if ($request->get('description') !== $token->getDescription()) {
            $descriptionChanged = true;
        }

        if ($request->get('facebookUrl') !== $token->getFacebookUrl() ||
            $request->get('telegramUrl') !== $token->getTelegramUrl() ||
            $request->get('twitterUrl') !== $token->getTwitterUrl() ||
            $request->get('discordUrl') !== $token->getDiscordUrl() ||
            $request->get('youtubeChannelId') !== $token->getYoutubeChannelId()) {
            $socialMediaChanged = true;
        }

        return [$socialMediaChanged, $descriptionChanged];
    }
}
