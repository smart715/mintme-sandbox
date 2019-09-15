<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\DeployCostFetcherInterface;
use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exception\ApiUnauthorizedException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Form\TokenType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\EmailAuthManagerInterface;
use App\Manager\Model\EmailAuthResultModel;
use App\Manager\TokenManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\TokenDeployInterface;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Verify\WebsiteVerifier;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;
use Throwable;

/**
 * @Rest\Route("/api/tokens")
 * @Security(expression="is_granted('prelaunch')")
 */
class TokensAPIController extends AbstractFOSRestController
{
    private const TOP_HOLDERS_COUNT = 10;

    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    /** @var int */
    private $expirationTime;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        UserActionLogger $userActionLogger,
        int $expirationTime = 60
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->userActionLogger = $userActionLogger;
        $this->expirationTime = $expirationTime;
    }


    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update", options={"expose"=true})
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="youtubeChannelId", nullable=true)
      *@Rest\RequestParam(name="code", nullable=true)
     */
    public function update(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        TwoFactorManagerInterface $twoFactorManager,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if ($request->get('name')
            && ($errorMessage = $this->getGoogleAuthenticatorErrorMessage($request->get('code'), $twoFactorManager))) {
                throw new ApiNotFoundException($errorMessage);
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if ($request->get('name') && !$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))) {
            throw new ApiBadRequestException('You need all your tokens to change token\'s name');
        }

        if ($request->get('name') && Token::NOT_DEPLOYED !== $token->deploymentStatus()) {
            throw new ApiBadRequestException('Token is deploying or deployed.');
        }

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

            throw new ApiBadRequestException('Invalid argument');
        }

        $this->em->persist($token);
        $this->em->flush();

        $this->userActionLogger->info('Change token info', $request->all());

        return $this->view(['tokenName' => $token->getName()], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/website-confirmation", name="token_website_confirm")
     * @Rest\RequestParam(name="url", allowBlank=false)
     */
    public function confirmWebsite(
        ParamFetcherInterface $request,
        WebsiteVerifier $websiteVerifier,
        string $name
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            return $this->view([
                'verified' => false,
                'errors' => ['File not downloaded yet'],
            ], Response::HTTP_ACCEPTED);
        }

        $url = $request->get('url');

        $validator = Validation::createValidator();
        $urlViolations = $validator->validate($url, new Url());

        if (0 < count($urlViolations)) {
            return $this->view([
                'verified' => false,
                'errors' => array_map(static function ($violation) {
                    return $violation->getMessage();
                }, iterator_to_array($urlViolations)),
            ], Response::HTTP_ACCEPTED);
        }

        $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());

        if ($isVerified) {
            $token->setWebsiteUrl($url);
            $this->em->flush();

            $this->userActionLogger->info('Website confirmed', [
                'token' => $token->getName(),
                'website' => $url,
            ]);
        }

        return $this->view([
            'verified' => $isVerified,
            'errors' => ['fileError' => $websiteVerifier->getError()],
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in")
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(name="released", allowBlank=false, requirements="(\d?[1-9]|[1-9]0)")
     * @Rest\RequestParam(name="releasePeriod", allowBlank=false)
     */
    public function setTokenReleasePeriod(
        string $name,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        TwoFactorManagerInterface $twoFactorManager
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $errorMessage = $this->getGoogleAuthenticatorErrorMessage($request->get('code'), $twoFactorManager);

        if ($errorMessage) {
            return $this->view($errorMessage, Response::HTTP_UNAUTHORIZED);
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
            $balance = $balanceHandler->balance($this->getUser(), $token);

            if ($balance->isFailed()) {
                return $this->view('Service unavailable now. Try later', Response::HTTP_BAD_REQUEST);
            }

            $releasedAmount = $balance->getAvailable()->divide(100)->multiply($request->get('released'));
            $lock->setAmountToRelease($balance->getAvailable()->subtract($releasedAmount))
                ->setReleasedAtStart((int)$releasedAmount->getAmount());
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
            throw $this->createNotFoundException('Token does not exist');
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
        if (!$this->getUser()) {
            throw new AccessDeniedHttpException();
        }

        try {
            $common = $balanceHandler->balances(
                $this->getUser(),
                $this->getUser()->getTokens()
            );
        } catch (BalanceException $exception) {
            if (BalanceException::EMPTY == $exception->getCode()) {
                $common = BalanceResultContainer::fail();
            } else {
                return $this->view(null, 500);
            }
        }

        $predefined = $balanceHandler->balances(
            $this->getUser(),
            $this->tokenManager->findAllPredefined()
        );

        return $this->view([
            'common' => $viewFactory->create($common),
            'predefined' => $viewFactory->create($predefined),
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
            throw $this->createNotFoundException('Token does not exist');
        }

        $balance = $balanceHandler->balance(
            $token->getProfile()->getUser(),
            $token
        )->getAvailable();

        if ($token->getLockIn()) {
            $balance = $balance->subtract($token->getLockIn()->getFrozenAmount());
        }

        return $this->view($balance);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/is-exchanged", name="is_token_exchanged", options={"expose"=true})
     */
    public function isTokenExchanged(string $name, BalanceHandlerInterface $balanceHandler): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
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
            throw $this->createNotFoundException('Token does not exist');
        }

        return $this->view(
            Token::NOT_DEPLOYED === $token->deploymentStatus()
        );
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/delete", name="token_delete", options={"expose"=true})
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function delete(
        ParamFetcherInterface $request,
        TwoFactorManagerInterface $twoFactorManager,
        EmailAuthManagerInterface $emailAuthManager,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isGoogleAuthenticatorEnabled() && !$twoFactorManager->checkCode($user, $request->get('code'))) {
            throw new ApiUnauthorizedException('Invalid 2fa code');
        } elseif (!$user->isGoogleAuthenticatorEnabled()) {
            $response = $emailAuthManager->checkCode($user, $request->get('code'));

            if (!$response->getResult()) {
                throw new ApiUnauthorizedException($response->getMessage());
            }
        }

        if (!$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))) {
            throw new ApiBadRequestException('You need all your tokens to delete token');
        }

        $this->em->remove($token);
        $this->em->flush();

        $this->userActionLogger->info('Delete token', $request->all());

        return $this->view(['message' => 'Token successfully deleted'], Response::HTTP_ACCEPTED);
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
            throw new ApiNotFoundException('Token does not exist');
        }

        $user = $this->getUser();
        $message = null;

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $emailAuthManager->generateCode($user, $this->expirationTime);
            $mailer->sendAuthCodeToMail(
                'Confirm token deletion',
                'Your code to confirm token deletion:',
                $user
            );
            $message = "Code for confirmation of token deletion was send to email.";
        }

        return $this->view(['message' => $message], Response::HTTP_ACCEPTED);
    }

    private function getGoogleAuthenticatorErrorMessage(string $code, TwoFactorManagerInterface $twoFactorManager): ?string
    {
        $user = $this->getUser();

        if (!$user) {
            return 'Invalid user';
        }

        if ($user->isGoogleAuthenticatorEnabled()
            && !$twoFactorManager->checkCode($user, $code)) {
            return 'Invalid 2fa code';
        }

        return null;
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
            throw $this->createNotFoundException('Not Found');
        }

        $topTraders = $balanceHandler->topHolders($tradable, self::TOP_HOLDERS_COUNT);

        return $this->view($topTraders, Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/deploy", name="token_deploy_balances", options={"expose"=true})
     */
    public function tokenDeployBalances(
        string $name,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        DeployCostFetcherInterface $costFetcher
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        try {
            $balances = [
                'balance' => $moneyWrapper->format(
                    $balanceHandler->balance($this->getUser(), Token::getFromSymbol(Token::WEB_SYMBOL))->getAvailable()
                ),
                'webCost' => $costFetcher->getDeployWebCost(),
            ];
        } catch (Throwable $ex) {
            throw new ApiBadRequestException();
        }

        return $this->view($balances, Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/deploy", name="token_deploy", options={"expose"=true})
     */
    public function deploy(
        string $name,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper,
        ContractHandlerInterface $contractHandler,
        DeployCostFetcherInterface $costFetcher
    ): View {
        $token = $this->tokenManager->findByName(
            (new StringConverter(new ParseStringStrategy()))->convert($name)
        );

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if (!$token->getLockIn()) {
            throw new ApiBadRequestException('Token not has released period');
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException('Unauthorized');
        }

        try {
            $cost = $costFetcher->getDeployWebCost();
            $balance = $balanceHandler
                ->balance($this->getUser(), Token::getFromSymbol(Token::WEB_SYMBOL))->getAvailable();

            if ($balanceHandler->balance(
                $this->getUser(),
                Token::getFromSymbol(Token::WEB_SYMBOL)
            )->getAvailable()->greaterThan($balance)) {
                throw new ApiBadRequestException('Low balance');
            }

            $contractHandler->deploy($token);

            $balanceHandler->withdraw(
                $this->getUser(),
                Token::getFromSymbol(Token::WEB_SYMBOL),
                $moneyWrapper->parse($cost, Token::WEB_SYMBOL)
            );

            $token->setPendingDeployment();
            $token->setDeployCost($cost);
            $this->em->persist($token);
            $this->em->flush();
        } catch (Throwable $ex) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $this->userActionLogger->info('Deploy Token', ['name' => $name]);

        return $this->view();
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/contract/update", name="token_contract_update", options={"expose"=true})
     * @Rest\RequestParam(name="address", allowBlank=false)
     * @Rest\RequestParam(name="lock", requirements="true|false")
     */
    public function contractUpdate(
        string $name,
        ParamFetcherInterface $request,
        ContractHandlerInterface $contractHandler
    ): View {
        $token = $this->tokenManager->findByName(
            (new StringConverter(new ParseStringStrategy()))->convert($name)
        );

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if ($token->isMinDestinationLocked()) {
            throw new ApiBadRequestException('Can\'t change the address');
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException('Unauthorized');
        }

        try {
            $contractHandler->updateMinDestination($token, $request->get('address'), $request->get('lock'));
            $token->setMinDestination($request->get('address'));

            if ("true" === $request->get('lock')) {
                $token->lockMinDestination();
            }

            $this->em->persist($token);
            $this->em->flush();
        } catch (Throwable $ex) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $this->userActionLogger->info('Update token minDestination', ['name' => $name]);

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }
}
