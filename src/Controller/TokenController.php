<?php

namespace App\Controller;

use App\Entity\Crypto;
use App\Entity\Token\Token;
use App\Exchange\Balance\BalanceHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Order;
use App\Exchange\Trade\TraderInterface;
use App\Form\TokenCreateType;
use App\Manager\CryptoManagerInterface;
use App\Manager\MarketManagerInterface;
use App\Manager\ProfileManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Verify\WebsiteVerifierInterface;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/** @Route("/token") */
class TokenController extends AbstractController
{
    /** @var EntityManagerInterface */
    protected $em;

    /** @var ProfileManagerInterface */
    protected $profileManager;

    /** @var TokenManagerInterface */
    protected $tokenManager;

    /** @var CryptoManagerInterface */
    protected $cryptoManager;

    /** @var MarketManagerInterface */
    protected $marketManager;
    
    /** @var TraderInterface */
    protected $trader;

    public function __construct(
        EntityManagerInterface $em,
        ProfileManagerInterface $profileManager,
        TokenManagerInterface $tokenManager,
        CryptoManagerInterface $cryptoManager,
        MarketManagerInterface $marketManager,
        TraderInterface $trader
    ) {
        $this->em = $em;
        $this->profileManager = $profileManager;
        $this->tokenManager = $tokenManager;
        $this->cryptoManager = $cryptoManager;
        $this->marketManager = $marketManager;
        $this->trader = $trader;
    }

    /**
     * @Route("/{name}/{tab}",
     *     name="token_show",
     *     defaults={"tab" = "trade"},
     *     methods={"GET"},
     *     requirements={"tab" = "trade|intro"}
     * )
     */
    public function show(string $name, NormalizerInterface $normalizer, ?string $tab): Response
    {
        $token = $this->tokenManager->findByName($name);

        if (null === $token) {
            return $this->render('pages/token_404.html.twig');
        }

        $isOwner = $token === $this->tokenManager->getOwnToken();

        $webCrypto = $this->cryptoManager->findBySymbol('WEB');

        $market = $webCrypto
            ? $this->marketManager->getMarket($webCrypto, $token)
            : null;

        $marketName = $market
            ? [
                'hiddenName' => $market->getHiddenName(),
                'tokenName' => $market->getTokenName(),
                'currncySymbol' => $market->getCurrencySymbol(),
            ]
            : [];

        return $this->render('pages/token.html.twig', [
            'token' => $token,
            'stats' => $normalizer->normalize($token->getLockIn(), null, [
                'groups' => [ 'Default' ],
            ]),
            'profile' => $token->getProfile(),
            'isOwner' => $isOwner,
            'tab' => $tab,
            'marketName' => $marketName,
        ]);
    }

    /** @Route(name="token_create") */
    public function create(Request $request, BalanceHandlerInterface $balanceHandler): Response
    {
        if ($this->isTokenCreated()) {
            return $this->redirectToOwnToken();
        }

        $token = new Token();
        $form = $this->createForm(TokenCreateType::class, $token);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $this->isProfileCreated()) {
            $profile = $this->profileManager->getProfile($this->getUser());

            if (null !== $profile) {
                $token->setProfile($profile);
                $this->em->persist($token);
                $this->em->flush();
            }

            try {
                $balanceHandler->withdraw(
                    $this->getUser(),
                    $token,
                    $this->getParameter('token_quantity')
                );

                return $this->redirectToOwnToken(); //FIXME: redirecto to introduction token page
            } catch (\Throwable $exception) {
                $this->em->remove($token);
                $this->em->flush();
                $this->addFlash('error', 'Exchanger connection lost. Try again.');
            }
        }

        return $this->render('pages/token_creation.html.twig', [
            'formHeader' => 'Plant your own token',
            'form' => $form->createView(),
            'profileCreated' => $this->isProfileCreated(),
        ]);
    }

    /**
     * @Route("/{name}/website-confirmation", name="token_website_confirmation")
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

        $fileContent = WebsiteVerifierInterface::PREFIX.': '.$token->getWebsiteConfirmationToken();
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'mintme.html'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    public function placeOrder(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $token = $this->tokenManager->findByName($data['tokenName']);
        $crypto = $this->cryptoManager->findBySymbol('WEB');
        $market = $this->marketManager->getMarket($crypto, $token);
        $user = $this->getUser();
        $side = 'sell' == $data['action'] ? 1
            : 2;

        $order = new Order(
            null,
            $user->getId(),
            null,
            $market,
            $data['amountInput'],
            $side,
            $data['priceInput'],
            "pending"
        );

        $tradeResult = $this->trader->placeOrder($order);

        return new JsonResponse([
            'result' => $tradeResult->getResult(),
            'message' => $tradeResult->getMessage(),
        ]);
    }

    public function fetchBalanceWeb(BalanceHandlerInterface $balanceHandler): JsonResponse
    {
        $user = $this->getUser();
        $balance = $balanceHandler->balanceWeb($user);

        return new JsonResponse([
            'available' => $balance->getAvailable(),
            'freeze' => $balance->getFreeze(),
        ]);
    }

    private function redirectToOwnToken(): RedirectResponse
    {
        $token = $this->tokenManager->getOwnToken();

        if (null === $token) {
            //FIXME: return "token not exist" template instead
            throw $this->createNotFoundException('User doesn\'t have a token created.');
        }

        return $this->redirectToRoute('token_show', [
            'name' => $token->getName(),
        ]);
    }

    private function isTokenCreated(): bool
    {
        return null !== $this->tokenManager->getOwnToken();
    }

    private function isProfileCreated(): bool
    {
        return null !== $this->profileManager->getProfile($this->getUser());
    }
}
