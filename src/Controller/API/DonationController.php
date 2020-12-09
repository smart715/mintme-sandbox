<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\Exception\FetchException;
use App\Entity\User;
use App\Exception\ApiBadRequestException;
use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use App\Logger\DonationLogger;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/donate")
 */
class DonationController extends AbstractFOSRestController
{
    /** @var DonationHandlerInterface */
    protected $donationHandler;

    /** @var MarketHandlerInterface */
    protected $marketHandler;

    /** @var DonationLogger */
    protected $logger;

    public function __construct(
        DonationHandlerInterface $donationHandler,
        MarketHandlerInterface $marketHandler,
        DonationLogger $logger
    ) {
        $this->donationHandler = $donationHandler;
        $this->marketHandler = $marketHandler;
        $this->logger = $logger;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/{base}/{quote}/check/{currency}/{amount}",
     *     name="check_donation",
     *     options={"expose"=true},
     *     requirements={"currency"="^(WEB|BTC|ETH)$"}
     * )
     * @Rest\RequestParam(name="amount", allowBlank=false, description="Amount to donate.")
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     description="Selected currency to donate."
     * )
     */
    public function checkDonation(
        Market $market,
        string $currency,
        string $amount
    ): View {
        try {
            /** @var User|null $user */
            $user = $this->getUser();

            $checkDonationResult = $this->donationHandler->checkDonation(
                $market,
                $currency,
                $amount,
                $user
            );

            $tokensWorth = $this->donationHandler->getTokensWorth($checkDonationResult->getTokensWorth(), $currency);
            $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market);
            $sellOrdersSummary = $this->donationHandler->getTokensWorth($sellOrdersSummary, $currency);

            return $this->view([
                'amountToReceive' => $checkDonationResult->getExpectedTokens(),
                'tokensWorth' => $tokensWorth,
                'sellOrdersSummary' => $sellOrdersSummary,
            ]);
        } catch (ApiBadRequestException $ex) {
            return $this->view([
                'message' => $ex->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $ex) {
            $message = $ex->getMessage();

            $this->logger->error(
                '[check_donation] Failed to check donation.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'market' => $market,
                    'currency' => $currency,
                    'amount' => $amount,
                ]
            );

            return $this->view([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{base}/{quote}/make", name="make_donation", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     requirements="(WEB|BTC|ETH)",
     *     description="Selected currency to donate."
     * )
     * @Rest\RequestParam(name="amount", allowBlank=false, description="Amount to donate.")
     * @Rest\RequestParam(
     *     name="expected_count_to_receive",
     *     allowBlank=false,
     *     description="Expected tokens count to receive."
     * )
     */
    public function makeDonation(Market $market, ParamFetcherInterface $request): View
    {
        $this->denyAccessUnlessGranted('new-trades');
        $this->denyAccessUnlessGranted('trading');

        try {
            $user = $this->getCurrentUser();
            $sellOrdersSummary = $this->marketHandler->getSellOrdersSummary($market);

            $this->donationHandler->makeDonation(
                $market,
                $request->get('currency'),
                (string)$request->get('amount'),
                (string)$request->get('expected_count_to_receive'),
                $user,
                $sellOrdersSummary
            );

            return $this->view(null, Response::HTTP_OK);
        } catch (ApiBadRequestException $ex) {
            return $this->view([
                'message' => $ex->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $ex) {
            $message = $ex->getMessage();

            $this->logger->error(
                '[make_donation] Failed to make donation.',
                [
                    'message' => $message,
                    'code' => $ex->getCode(),
                    'market' => $market,
                ]
            );

            return $this->view([
                'error' => $message,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getCurrentUser(): User
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
