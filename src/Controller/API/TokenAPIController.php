<?php

namespace App\Controller\API;

use App\Entity\Token\LockIn;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenType;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\Validation;

/** @Rest\Route("/api/token") */
class TokenAPIController extends FOSRestController
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var MarketManagerInterface */
    protected $marketManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketManagerInterface $marketManager
    ) {
        $this->em = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
    }

    /**
     * @Rest\View()
     * @Rest\Patch("/{name}", name="token_update")
     * @Rest\RequestParam(name="_csrf_token", allowBlank=false)
     * @Rest\RequestParam(name="name", nullable=true)
     * @Rest\RequestParam(name="description", nullable=true)
     */
    public function update(ParamFetcherInterface $request, string $name): View
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $form = $this->createForm(TokenType::class, $token, [
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);

        $form->submit(array_filter($request->all(), function ($value) {
            return null !== $value;
        }), false);

        $csrfToken = $request->get('_csrf_token');

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $csrfToken)) {
            return $this->view($form, Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($token);
        $this->em->flush();

        return $this->view($token, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/website-confirmation", name="token_website_confirm")
     * @Rest\RequestParam(name="url", allowBlank=false)
     */
    public function confirmWebsite(
        ParamFetcherInterface $request,
        WebsiteVerifierInterface $websiteVerifier,
        string $name
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (null === $token->getWebsiteConfirmationToken()) {
            return $this->view(null, Response::HTTP_BAD_REQUEST);
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
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $isVerified = $websiteVerifier->verify($url, $token->getWebsiteConfirmationToken());

        if ($isVerified) {
            $token->setWebsiteUrl($url);
            $this->em->flush();
        }

        return $this->view(['verified' => $isVerified, 'errors' => []], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{name}/lock-in", name="lock_in")
     * @Rest\RequestParam(name="released", allowBlank=false)
     * @Rest\RequestParam(name="releasePeriod", allowBlank=false)
     * @Rest\RequestParam(name="_csrf_token", allowBlank=false)
     */
    public function setTokenReleasePeriod(
        ParamFetcherInterface $request,
        BalanceHandlerInterface $balanceHandler,
        string $name
    ): View {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist');
        }

        $this->denyAccessUnlessGranted('edit', $token);

        $lock = $token->getLockIn() ?? new LockIn($token);

        $form = $this->createFormBuilder($lock, [
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ])
            ->add('releasePeriod')
            ->getForm();

        $form->submit($request->all());

        if (!$form->isValid() || !$this->isCsrfTokenValid('update-token', $request->get('_csrf_token'))) {
            return $this->view($form);
        }

        if (!$lock->getId()) {
            $balance = $balanceHandler->balance($this->getUser(), $token);

            if ($balance->isFailed()) {
                return $this->view('Exchanger connection lost. Try again.', Response::HTTP_BAD_REQUEST);
            }

            $releasedAmount = $balance->getAvailable() / 100 * $request->get('released');
            $lock->setAmountToRelease($balance->getAvailable() - $releasedAmount);
        }

        $this->em->persist($lock);
        $this->em->flush();

        return $this->view($lock);
    }

    /**
    * @Rest\View()
    * @Rest\Post("/{tokenName}/place-order", name="token_place_order")
    * @Rest\RequestParam(name="tokenName", allowBlank=false)
    * @Rest\RequestParam(name="priceInput", allowBlank=false)
    * @Rest\RequestParam(name="amountInput", allowBlank=false)
    * @Rest\RequestParam(name="action", allowBlank=false)
    */
    public function placeOrder(ParamFetcherInterface $request, TraderInterface $trader): View
    {
        $token = $this->tokenManager->findByName($request->get('tokenName'));
        $crypto = $this->cryptoManager->findBySymbol('WEB');
        
        if (null === $token || null === $crypto)
            throw $this->createNotFoundException('Token or Crypto not found.');

        $market = $this->marketManager->getMarket($crypto, $token);

        if (null === $market)
            throw $this->createNotFoundException('Market not found.');
        
        $order = new Order(
            null,
            $this->getUser()->getId(),
            null,
            $market,
            $request->get('amountInput'),
            Order::SIDE_MAP[$request->get('action')],
            $request->get('priceInput'),
            Order::PENDING_STATUS
        );

        $tradeResult = $trader->placeOrder($order);

        return $this->view(
            [
                'result' => $tradeResult->getResult(),
                'message' => $tradeResult->getMessage(),
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
    * @Rest\Get("/{tokenName}/get-balance-token", name="fetch_balance_token")
    * @Rest\RequestParam(name="tokenName", allowBlank=false)
    */
    public function fetchBalanceToken(string $tokenName, BalanceHandlerInterface $balanceHandler): View
    {
        $user = $this->getUser();
        $token = $this->tokenManager->findByName($tokenName);

        if (null === $token) {
            throw $this->createNotFoundException('Token does not exist.');
        }

        $balance = $balanceHandler->balance($user, $token);

        return $this->view(
            [
                'available' => $balance->getAvailable(),
                'freeze' => $balance->getFreeze(),
            ],
            Response::HTTP_ACCEPTED
        );
    }

    /**
    * @Rest\Get("/get-balance-web", name="fetch_balance_web")
    */
    public function fetchBalanceWeb(BalanceHandlerInterface $balanceHandler): View
    {
        $user = $this->getUser();
        $balance = $balanceHandler->balance($user, Token::getWeb());

        return $this->view(
            [
                'available' => $balance->getAvailable(),
                'freeze' => $balance->getFreeze(),
            ],
            Response::HTTP_ACCEPTED
        );
    }
}
