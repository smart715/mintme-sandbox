<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\Token\LockIn;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiNotFoundException;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Balance\Exception\BalanceException;
use App\Exchange\Balance\Factory\BalanceViewFactoryInterface;
use App\Exchange\Balance\Model\BalanceResultContainer;
use App\Form\TokenType;
use App\Logger\UserActionLogger;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Manager\TwoFactorManagerInterface;
use App\Utils\Converter\String\ParseStringStrategy;
use App\Utils\Converter\String\StringConverter;
use App\Utils\Verify\WebsiteVerifier;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/**
 * @Rest\Route("/api/tokens")
 * @Security(expression="is_granted('prelaunch')")
 */
class TokensAPIController extends AbstractFOSRestController
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var UserActionLogger */
    private $userActionLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        UserActionLogger $userActionLogger
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->userActionLogger = $userActionLogger;
    }


    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update")
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     * @Rest\RequestParam(name="facebookUrl", nullable=true)
     * @Rest\RequestParam(name="telegramUrl", nullable=true)
     * @Rest\RequestParam(name="discordUrl", nullable=true)
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
                $this->getUser()->getRelatedTokens()
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
}
