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
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Form\TokenType;
use App\Logger\UserActionLogger;
use App\Mailer\MailerInterface;
use App\Manager\CryptoManagerInterface;
use App\Manager\EmailAuthManagerInterface;
use App\Manager\TokenManagerInterface;
use App\SmartContract\ContractHandlerInterface;
use App\SmartContract\DeploymentFacadeInterface;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Verify\WebsiteVerifier;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Money\Currency;
use Money\Money;
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
class TokensController extends AbstractFOSRestController
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

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        UserActionLogger $userActionLogger,
        int $topHolders = 10,
        int $expirationTime = 60
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->userActionLogger = $userActionLogger;
        $this->topHolders = $topHolders;
        $this->expirationTime = $expirationTime;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update", options={"2fa"="optional", "expose"=true})
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="telegramUrl", nullable=true)
     * @Rest\RequestParam(name="discordUrl", nullable=true)
     * @Rest\RequestParam(name="youtubeChannelId", nullable=true)
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function update(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        $name = (new StringConverter(new ParseStringStrategy()))->convert($name);

        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if ($request->get('name') && !$balanceHandler->isNotExchanged($token, $this->getParameter('token_quantity'))) {
            throw new ApiBadRequestException('You need all your tokens to change token\'s name');
        }

        if ($request->get('name') && Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
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
                ], Response::HTTP_ACCEPTED);
            }

            $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());
            $message = 'Website confirmed';
        } else {
            $message = 'Website deleted';
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
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in", options={"expose"=true})
     * @Rest\RequestParam(name="code", nullable=true)
     * @Rest\RequestParam(name="released", allowBlank=false, requirements="^[0-9][0-9]?$|^100$")
     * @Rest\RequestParam(name="releasePeriod", allowBlank=false)
     */
    public function setTokenReleasePeriod(
        string $name,
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        MoneyWrapperInterface $moneyWrapper
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
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
            /** @var  \App\Entity\User $user*/
            $user = $this->getUser();
            $balance = $balanceHandler->balance($user, $token);

            if ($balance->isFailed()) {
                return $this->view('Service unavailable now. Try later', Response::HTTP_BAD_REQUEST);
            }

            $releasedAmount = $balance->getAvailable()->divide(100)->multiply($request->get('released'));
            $tokenQuantity = $moneyWrapper->parse((string)$this->getParameter('token_quantity'), Token::TOK_SYMBOL);
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

        /** @var  \App\Entity\User $user*/
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
            $balance = $token->isDeployed()
                ? $balance = $balance->subtract($token->getLockIn()->getFrozenAmountWithReceived())
                : $balance = $balance->subtract($token->getLockIn()->getFrozenAmount());
        }

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

        return $this->view(new Money($withdrawn, new Currency(MoneyWrapper::TOK_SYMBOL)));
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
            Token::NOT_DEPLOYED === $token->getDeploymentStatus()
        );
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

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException('Token is deploying or deployed.');
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isGoogleAuthenticatorEnabled()) {
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

        $this->addFlash('success', "Token {$token->getName()} was successfully deleted.");

        $mailer->sendTokenDeletedMail($token);

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

        /** @var  \App\Entity\User $user*/
        $user = $this->getUser();
        $message = null;

        if (!$user->isGoogleAuthenticatorEnabled()) {
            $emailAuthManager->generateCode($user, $this->expirationTime);
            $mailer->sendAuthCodeToMail(
                'Confirm token deletion',
                'Your code to confirm token deletion:',
                $user
            );
            $message = "Code to confirm token deletion was sent to your email.";
        }

        return $this->view(['message' => $message], Response::HTTP_ACCEPTED);
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

        $topTraders = $balanceHandler->topHolders(
            $tradable,
            $this->topHolders,
            $this->topHolders + 5,
            5,
            $this->topHolders * 4
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
            throw new ApiNotFoundException('Token does not exist');
        }

        try {
            /** @var  \App\Entity\User $user*/
            $user = $this->getUser();

            $balances = [
                'balance' => $balanceHandler->balance(
                    $user,
                    Token::getFromSymbol(Token::WEB_SYMBOL)
                )->getAvailable(),
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
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function deploy(
        string $name,
        DeploymentFacadeInterface $deployment
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if (Token::NOT_DEPLOYED !== $token->getDeploymentStatus()) {
            throw new ApiBadRequestException('Token already deployed or deploying');
        }

        if (!$token->getLockIn()) {
            throw new ApiBadRequestException('Token not has released period');
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException('Unauthorized');
        }

        try {
            /** @var  \App\Entity\User $user*/
            $user = $this->getUser();

            $deployment->execute($user, $token);
        } catch (Throwable $ex) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $this->userActionLogger->info('Deploy Token', ['name' => $name]);

        return $this->view();
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/contract/update", name="token_contract_update", options={"2fa"="optional", "expose"=true})
     * @Rest\RequestParam(name="address", allowBlank=false)
     * @Rest\RequestParam(name="code", nullable=true)
     */
    public function contractUpdate(
        string $name,
        ParamFetcherInterface $request,
        ContractHandlerInterface $contractHandler
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException('Unauthorized');
        }

        try {
            $contractHandler->updateMintDestination($token, $request->get('address'));
            $token->setUpdatingMintDestination();

            $this->em->persist($token);
            $this->em->flush();
        } catch (Throwable $ex) {
            throw new ApiBadRequestException('Internal error, Please try again later');
        }

        $this->userActionLogger->info('Update token mintDestination', ['name' => $name]);

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/sold", name="token_sold_on_market", options={"expose"=true})
     */
    public function soldOnMarket(
        string $name,
        BalanceHandlerInterface $balanceHandler,
        MarketHandlerInterface $marketHandler
    ): View {
        $crypto = $this->cryptoManager->findBySymbol(Token::WEB_SYMBOL);
        $token = $this->tokenManager->findByName($name);

        if (null === $crypto || null === $token) {
            throw new ApiNotFoundException('Token does not exist');
        }

        $ownerPendingOrders = $marketHandler->getPendingOrdersByUser(
            $token->getProfile()->getUser(),
            [new Market($crypto, $token)]
        );

        return $this->view(
            $balanceHandler->soldOnMarket($token, $this->getParameter('token_quantity'), $ownerPendingOrders),
            Response::HTTP_OK
        );
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{name}/check-token-name-exists", name="check_token_name_exists", options={"expose"=true})
     */
    public function checkTokenNameExists(string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        return $this->view(['exists' => null !== $token], Response::HTTP_OK);
    }
}
