<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\TokenPromotionCostFetcherInterface;
use App\Config\HideFeaturesConfig;
use App\Config\TokenPromotionConfig;
use App\Exception\ApiBadRequestException;
use App\Exception\ApiUnauthorizedException;
use App\Manager\CryptoManagerInterface;
use App\Manager\TokenManager;
use App\Manager\TokenPromotionManagerInterface;
use App\Utils\LockFactory;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Rest\Route("/api/token")
 */
class TokenPromotionController extends AbstractFOSRestController
{
    private TokenPromotionManagerInterface $tokenPromotionManager;
    private TokenManager $tokenManager;
    private TranslatorInterface $translator;
    private CryptoManagerInterface $cryptoManager;
    private HideFeaturesConfig $hideFeaturesConfig;
    private LoggerInterface $logger;

    public function __construct(
        TokenPromotionManagerInterface $tokenPromotionManager,
        TokenManager $tokenManager,
        TranslatorInterface $translator,
        CryptoManagerInterface $cryptoManager,
        HideFeaturesConfig $hideFeaturesConfig,
        LoggerInterface $logger
    ) {
        $this->tokenPromotionManager = $tokenPromotionManager;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->cryptoManager = $cryptoManager;
        $this->hideFeaturesConfig = $hideFeaturesConfig;
        $this->logger = $logger;
    }


    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/promotions/active/{tokenName}",
     *     name="token_promotions_active",
     *     options={"expose"=true}
     * )
     */
    public function getTokenActivePromotions(string $tokenName): View
    {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        if (!$this->isGranted('edit', $token)) {
            throw new ApiUnauthorizedException($this->translator->trans('api.tokens.unauthorized'));
        }

        return $this->view($this->tokenPromotionManager->findActivePromotionsByToken($token), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post(
     *     "/promotions/buy/{tokenName}",
     *     name="token_promotions_buy",
     *     options={"expose"=true}
     * )
     * @Rest\RequestParam(name="tariff", nullable=false)
     * @Rest\RequestParam(name="currency", nullable=false)
     */
    public function buyTokenPromotion(
        string $tokenName,
        ParamFetcherInterface $request,
        TokenPromotionConfig $tokenPromotionConfig,
        LockFactory $lockFactory
    ): View {
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException($this->translator->trans('api.tokens.token_not_exists'));
        }

        $this->denyAccessUnlessGranted('edit', $token);

        if (count($this->tokenPromotionManager->findActivePromotionsByToken($token)) > 0) {
            throw new AccessDeniedException();
        }

        $tariff = $tokenPromotionConfig->getTariff((string)$request->get('tariff'));
        $payCurrency = $this->cryptoManager->findBySymbol((string)$request->get('currency'));

        if (!$tariff || !$payCurrency || !$this->hideFeaturesConfig->isCryptoEnabled($payCurrency->getSymbol())) {
            throw new ApiBadRequestException();
        }

        $userId = $token->getOwner()->getId();
        $lock = $lockFactory->createLock(LockFactory::LOCK_BALANCE . $userId);

        if (!$lock->acquire()) {
            throw new AccessDeniedException();
        }

        try {
            $promotion = $this->tokenPromotionManager->buyPromotion($token, $tariff, $payCurrency);

            return $this->view($promotion, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $this->logger->error('Error buying token promotion: ' . $e->getMessage());

            return $this->view([], Response::HTTP_BAD_REQUEST);
        } finally {
            $lock->release();
        }
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/promotions/costs",
     *     name="token_promotions_costs",
     *     options={"expose"=true},
     * )
     */
    public function getTokenPromotionCosts(TokenPromotionCostFetcherInterface $costFetcher): View
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        try {
            return $this->view($costFetcher->getCosts());
        } catch (\Throwable $e) {
            $this->logger->error('Error fetching token promotion costs: ' . $e->getMessage());

            return $this->view([
                'message' => $this->translator->trans('toasted.error.external'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
