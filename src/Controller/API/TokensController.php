<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\DeployCostFetcherInterface;
use App\Controller\TwoFactorAuthenticatedInterface;
use App\Entity\Crypto;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exception\ApiUnauthorizedException;
use App\Exception\InvalidAddressException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Exchange\Market;
use App\Form\TokenType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\BlacklistManager;
use App\Manager\BlacklistManagerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\EmailAuthManagerInterface;
use App\Manager\MarketStatusManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\UserNotificationManagerInterface;
use App\Notifications\Strategy\NotificationContext;
use App\Notifications\Strategy\TokenDeployedNotificationStrategy;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\DeploymentFacadeInterface;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\NotificationTypes;
use App\Utils\Symbols;
use App\Utils\Verify\WebsiteVerifier;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @Rest\Route("/api/tokens")
 */
class TokensController extends AbstractFOSRestController implements TwoFactorAuthenticatedInterface
{

    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var int */
    private $topHolders;

    /** @var int */
    private $expirationTime;

    /** @var BlacklistManagerInterface */
    protected $blacklistManager;

    /** @var TranslatorInterface */
    private $translator;

    /** @var UserNotificationManagerInterface */
    private UserNotificationManagerInterface $userNotificationManager;

    /** @var MailerInterface */
    private MailerInterface $mailer;

    private MarketStatusManagerInterface $marketStatusManager;

    private MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        UserActionLogger $userActionLogger,
        BlacklistManager $blacklistManager,
        TranslatorInterface $translator,
        UserNotificationManagerInterface $userNotificationManager,
        MailerInterface $mailer,
        MarketStatusManagerInterface $marketStatusManager,
        MoneyWrapperInterface $moneyWrapper,
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
        $this->userNotificationManager = $userNotificationManager;
        $this->mailer = $mailer;
        $this->marketStatusManager = $marketStatusManager;
        $this->moneyWrapper = $moneyWrapper;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/update/{name}", name="token_update_name", options={"2fa"="optional", "expose"=true})
     * @Rest\RequestParam(name="name", nullable=false)
     * @Rest\RequestParam(name="code", nullable=false)
     */
    public function updateName(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token || !$token->isMintmeToken()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (!$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.you_need_all_tokens_to_change_name'));
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.deploying'));
        }

        if ($this->blacklistManager->isBlacklistedToken($request->get('name'))) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.forbidden_name'));
        }

        $this->handleUpdateForm($token, $request);

        $this->em->persist($token);
        $this->em->flush();

        $this->userActionLogger->info('Change token info', $request->all());

        return $this->view(['tokenName' => $token->getName()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update", options={"expose"=true})
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="telegramUrl", nullable=true)
     * @Rest\RequestParam(name="discordUrl", nullable=true)
     * @Rest\RequestParam(name="youtubeChannelId", nullable=true)
     */
    public function update(
        ParamFetcherInterface $request,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $this->handleUpdateForm($token, $request);

        if (null === $token->getDescription() || '' == $token->getDescription()) {
            $token->setNumberOfReminder(0);
            $token->setNextReminderDate(new \DateTime('+1 month'));
        }

        $this->em->persist($token);
        $this->em->flush();

        $this->userActionLogger->info('Change token info', $request->all());

        if ($request->get('description')) {
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

            $this->userActionLogger->info($message, [
                'token' => $token->getName(),
                'website' => $url,
            ]);
        }

        return $this->view([
            'verified' => $isVerified,
            'errors' => ['fileError' => $websiteVerifier->getError()],
            'message' => $message.' successfully',
        ], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(name="released", allowBlank=false, requirements="^[2-9][0-9]$|^100$")
     * @Rest\RequestParam(name="releasePeriod", allowBlank=false)
     */
    public function setTokenReleasePeriod(
        string $name,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token || !$token->isMintmeToken()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException('Token is deploying or deployed.');
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

            $releasedAmount = $balance->getAvailable()->divide(100)->multiply($request->get('released'));
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
     */
    public function getTokens(BalanceHandlerInterface $balanceHandler, BalanceViewFactoryInterface $viewFactory): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user*/
        $user = $this->getUser();

        try {
            $common = $balanceHandler->balances(
                $user,
                $user->getTokens()
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
            $this->tokenManager->findAllPredefined()
        );

        return $this->view([
            'common' => $viewFactory->create($common, $user),
            'predefined' => $viewFactory->create($predefined, $user),
        ]);
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
            : 0;

        return $this->view(new Money($withdrawn, new Currency(Symbols::TOK)));
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
     * @Rest\Get("/{name}/is-not_deployed", name="is_token_not_deployed", options={"expose"=true})
     */
    public function isTokenNotDeployed(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view(
            Token::NOT_DEPLOYED === $token->getDeploymentStatus()
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/is-deployed", name="is_token_deployed", options={"expose"=true})
     */
    public function isTokenDeployed(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        return $this->view([Token::DEPLOYED => $token->getDeploymentStatus()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/address", name="token_address", options={"expose"=true})
     */
    public function getTokenContractAddress(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        return $this->view(['address' => $token->getAddress()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/tx_hash", name="token_tx_hash", options={"expose"=true})
     */
    public function getTokenTxHash(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        return $this->view(['txHash' => $token->getTxHash()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/deployed_date", name="token_deployed_date", options={"expose"=true})
     */
    public function getDeployedDate(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (!$token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        return $this->view(['deployedDate' => $token->getDeployed()], Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/delete", name="token_delete", options={"2fa"="optional", "expose"=true})
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function delete(
        ParamFetcherInterface $request,
        EmailAuthManagerInterface $emailAuthManager,
        BalanceHandlerInterface $balanceHandler,
        MailerInterface $mailer,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);
        $crypto = $this->cryptoManager->findBySymbol(Symbols::WEB);

        if (null === $token || !$token->isMintmeToken()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('delete', $token);

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException(
                $this->translator->trans('token.delete.body.deploying_or_deployed')
            );
        }

        $soldOnMarket = $this->getSoldOnMarket($token, $crypto);
        $soldLimit = $this->moneyWrapper->parse(
            (string)$this->getParameter('token_delete_sold_limit'),
            Symbols::TOK
        );

        if ($soldOnMarket->greaterThanOrEqual($soldLimit)) {
            throw new ApiBadRequestException(
                $this->translator->trans('token.delete.body.over_limit', ['%limit%' => $soldLimit->getAmount()])
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

        $this->em->remove($token);
        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('api.tokens.deleted', ['%tokeName%' => $token->getName()]));

        $mailer->sendTokenDeletedMail($token);

        $this->userActionLogger->info('Delete token', $request->all());

        return $this->view(
            ['message' => $this->translator->trans('api.tokens.delete_successfull')],
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/send-code", name="token_send_code", options={"expose"=true})
     */
    public function sendCode(
        MailerInterface $mailer,
        EmailAuthManagerInterface $emailAuthManager,
        string $name
    ): View {
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
            $mailer->sendAuthCodeToMail(
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

        if (null == $tradable) {
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
     * @Rest\Get("/{name}/deploy", name="token_deploy_balances", options={"expose"=true})
     */
    public function tokenDeployBalances(
        string $name,
        BalanceHandlerInterface $balanceHandler,
        DeployCostFetcherInterface $costFetcher
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        try {
            /** @var User $user*/
            $user = $this->getUser();

            $balances = [
                'balance' => $balanceHandler->balance(
                    $user,
                    Token::getFromSymbol($token->getCryptoSymbol())
                )->getAvailable(),
                'webCost' => $costFetcher->getDeployWebCost(),
            ];
        } catch (Throwable $ex) {
            throw new ApiBadRequestException();
        }

        return $this->view($balances, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/deploy", name="token_deploy", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     * @param string $name
     * @param DeploymentFacadeInterface $deployment
     * @return View
     * @throws ApiBadRequestException
     * @throws ApiNotFoundException
     * @throws ApiUnauthorizedException
     */
    public function deploy(
        string $name,
        DeploymentFacadeInterface $deployment
    ): View {
        $this->denyAccessUnlessGranted('deploy');

        $token = $this->tokenManager->findByName($name);

        if (null === $token || !$token->isMintmeToken()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException('Token already deployed or deploying');
        }

        if (!$token->getLockIn()) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.has_not_releaded_period'));
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unathorized'));
        }

        try {
            /** @var User $user*/
            $user = $this->getUser();

            $deployment->execute($user, $token);
        } catch (Throwable $ex) {
            throw new ApiBadRequestException($this->translator->trans('api.tokens.internal_error'));
        }

        $this->userActionLogger->info('Deploy Token', ['name' => $name]);

        $notificationType = NotificationTypes::TOKEN_DEPLOYED;
        $strategy = new TokenDeployedNotificationStrategy(
            $this->userNotificationManager,
            $this->mailer,
            $this->em,
            $token,
            $notificationType
        );
        $notificationContext = new NotificationContext($strategy);

        $tokenUsers = $token->getUsers();

        foreach ($tokenUsers as $tokenUser) {
            if ($tokenUser->getId() !== $user->getId()) {
                $notificationContext->sendNotification($tokenUser);
            }
        }

        return $this->view();
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/contract/update", name="token_contract_update", options={"2fa"="optional", "expose"=true})
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
        ContractHandlerInterface $contractHandler
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token || !$token->isMintmeToken()) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unathorized'));
        }

        try {
            if (!$this->validateEthereumAddress($request->get('address'))) {
                throw new InvalidAddressException('Invalid Ethereum address');
            }

            $contractHandler->updateMintDestination($token, $request->get('address'));
            $token->setUpdatingMintDestination();

            $this->em->persist($token);
            $this->em->flush();
        } catch (Throwable $ex) {
            if ($ex instanceof  InvalidAddressException) {
                throw new ApiBadRequestException('Invalid Address');
            } else {
                throw new ApiBadRequestException($this->translator->trans('api.tokens.internal_error'));
            }
        }

        $this->userActionLogger->info('Update token mintDestination', ['name' => $name]);

        return $this->view(null, Response::HTTP_NO_CONTENT);
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
        $crypto = $this->cryptoManager->findBySymbol(Symbols::WEB);
        $token = $this->tokenManager->findByName($name);

        if (null === $crypto || null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        return $this->view($this->getSoldOnMarket($token, $crypto), Response::HTTP_OK);
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
     * @Rest\Get("/tokens-creation", name="check_token_creation", options={"expose"=true})
     * @return View
     */
    public function isTokenCreationEnabled(): View
    {
        return $this->view([
            'tokenCreation' => $this->isGranted('new-trades') && $this->isGranted('trading'),
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
        $userToken = $this->tokenManager->getOwnTokenByName($tokenName);

        if (!$userToken || !$userToken->isMintmeToken()) {
            throw new AccessDeniedException();
        }

        $userToken->setShowDeployedModal(false);

        $this->em->persist($userToken);
        $this->em->flush();

        return $this->view([], Response::HTTP_OK);
    }

    private function getSoldOnMarket(Token $token, Crypto $crypto): Money
    {
        // todo: use marketStats.soldOnMarket instead of calling different gateway.
        $marketStatus = $this->marketStatusManager->getMarketStatus(new Market($crypto, $token));

        return $marketStatus
            ? $marketStatus->getSoldOnMarket()
            : $this->moneyWrapper->parse('0', Symbols::TOK);
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

                if ('name' === $childForm->getName()) {
                    throw new ApiBadRequestException($this->translator->trans('api.tokens.invalid_argument'));
                }
            }

            throw new ApiBadRequestException($this->translator->trans('api.tokens.invalid_argument'));
        }
    }
}
