<?php declare(strict_types = 1);

namespace App\Controller\Admin;

use App\Controller\Controller;
use App\Logger\UserActionLogger;
use App\Manager\Profit\ProfitManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Route("/admin-r8bn/profits")
 * @Security(expression="is_granted('ROLE_PROFIT_VIEWER')")
 */
class ProfitController extends Controller
{
    private ProfitManagerInterface $profitManager;
    private UserActionLogger $logger;
    public function __construct(
        NormalizerInterface $normalizer,
        ProfitManagerInterface $profitManager,
        UserActionLogger $logger
    ) {
        $this->profitManager = $profitManager;
        $this->logger = $logger;
        parent::__construct($normalizer);
    }

    /**
     * @Route("/services-profits", name="services_profits")
     */
    public function fetchServicesProfits(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        try {
            $deploymentProfits = $this->profitManager->getDeploymentProfit($startDate, $endDate);
            $additionalMarketsProfits = $this->profitManager->getAdditionalMarketsProfit($startDate, $endDate);
            $commentTipFeesProfit = $this->profitManager->getCommentTipFeesProfit($startDate, $endDate);
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching services profits', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while fetching profits');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/services.html.twig',
            [
                'deploymentProfits' => $deploymentProfits,
                'additionalMarketsProfits' => $additionalMarketsProfits,
                'commentTipFeesProfit' => $commentTipFeesProfit,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
            ]
        );
    }

    /**
     * @Route("/transactions-profits", name="transactions_profits")
     */
    public function fetchTransactionsProfits(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        try {
            $internalTransactionsProfits = $this->profitManager->getInternalTransactionsProfit($startDate, $endDate);
            $transactionsProfits = $this->profitManager->getTransactionsProfit($startDate, $endDate);
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching transactions profits', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while fetching profits');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/transactions.html.twig',
            [
                'transactionsProfits' => $transactionsProfits,
                'internalTransactionsProfits' => $internalTransactionsProfits,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
            ]
        );
    }

    /**
     * @Route("/trading-profits", name="trading_profits")
     */
    public function fetchTradingProfits(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        try {
            $tradingProfits = $this->profitManager->getTradingProfits($startDate, $endDate);
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching trading profits', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while fetching profits');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/trading.html.twig',
            [
                'tradingProfits' => $tradingProfits,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
            ]
        );
    }

    /**
     * @Route("/referral-profits", name="referrals_profits")
     */
    public function fetchReferralProfits(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        try {
            $tokenReferralProfits = $this->profitManager->getTokenReferralProfits($startDate, $endDate);
            $donationFeeReferralProfits = $this->profitManager->getDonationFeeReferralProfits($startDate, $endDate);
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching referral profits', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while fetching profits');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/referrals.html.twig',
            [
                'tokenReferralProfits' => $tokenReferralProfits,
                'donationFeeReferralProfits' => $donationFeeReferralProfits,
                'startDate' => $startDate ? $startDate->format('d-m-Y') : null,
                'endDate' => $endDate ? $endDate->format('d-m-Y') : null,
            ]
        );
    }

    /**
     * @Route("/bots-profits", name="bots_profits")
     */
    public function fetchBotsProfits(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        try {
            $botsProfits = $this->profitManager->getBotsProfits($startDate, $endDate);
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching bots profits', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while fetching profits');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/bots.html.twig',
            [
                'botsProfits' => $botsProfits,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
            ]
        );
    }

     /**
     * @Route("/profits-summary", name="profits_summary")
     */
    public function fetchProfitsSummary(Request $request): Response
    {
        if ($this->isReferredFromOtherProfitTab($request)) {
            return $this->redirectHereReferrerStartEndParams($request);
        }

        [$startDate, $endDate] = $this->extractIntervalDates($request);

        $withMintMe = (bool)$request->get('with_mintme');
        $withTrackedAccounts = (bool)$request->get('with_tracked_accounts');

        try {
            $profitsSummary = $this->profitManager->getProfitsSummary(
                $startDate,
                $endDate,
                $withMintMe,
                $withTrackedAccounts
            );
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong while fetching profits summary', [
                'errorMessage' => $e->getMessage(),
                'stackTrace' => $e->getTraceAsString(),
            ]);
            $this->addFlash('error', 'Something went wrong while profits summary');

            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return $this->render(
            'admin/profits/summary.html.twig',
            [
                'profitsSummary' => $profitsSummary,
                'startDate' => $startDate->format('d-m-Y'),
                'endDate' => $endDate->format('d-m-Y'),
                'withMintMe' => $withMintMe,
                'withTrackedAccounts' => $withTrackedAccounts,
            ]
        );
    }

    private function isReferredFromOtherProfitTab(Request $request): bool
    {
        $referrerRequest = Request::create($request->headers->get('referer'));

        return $referrerRequest->get('start_date') &&
            $referrerRequest->get('end_date') &&
            !$request->get('start_date') &&
            !$request->get('end_date') &&
            $referrerRequest->getPathInfo() !== $request->getPathInfo();
    }

    private function redirectHereReferrerStartEndParams(Request $request): RedirectResponse
    {
        $referrerRequest = Request::create($request->headers->get('referer'));

        return $this->redirectToRoute($request->get('_route'), [
            'start_date' => $referrerRequest->get('start_date'),
            'end_date' => $referrerRequest->get('end_date'),
        ]);
    }

    /**
     * @return array<\DateTimeImmutable|null>
     * @throws \Exception
     */
    private function extractIntervalDates(
        Request $request,
        ?string $startDateDefault = "1 months ago midnight",
        ?string $endDateDefault = "today midnight"
    ): array {
        $startParam = \DateTimeImmutable::createFromFormat('d-m-Y|', (string)$request->get('start_date'));
        $endParam = \DateTimeImmutable::createFromFormat('d-m-Y|', (string)$request->get('end_date'));

        if ($startParam && $endParam) {
            return [$startParam, $endParam];
        }

        $startDate = $startDateDefault
            ? new \DateTimeImmutable($startDateDefault)
            : null;
        $endDate = $endDateDefault
            ? new \DateTimeImmutable($endDateDefault)
            : null;

        return [$startDate, $endDate];
    }
}
