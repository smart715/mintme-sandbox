<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\User;
use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\Market;
use App\Exchange\Market\MarketHandlerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/donation")
 */
class DonationController extends AbstractFOSRestController
{
    /** @var DonationHandlerInterface */
    protected $donationHandler;

    public function __construct(DonationHandlerInterface $donationHandler)
    {
        $this->donationHandler = $donationHandler;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{base}/{quote}/check/{currency}/{amount}", name="check_donation", options={"expose"=true})
     * @Rest\RequestParam(name="amount", allowBlank=false, description="Amount to donate.")
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     requirements="(WEB|BTC)",
     *     description="Selected currency to donate."
     * )
     */
    public function checkDonation(
        Market $market,
        string $currency,
        string $amount,
        MarketHandlerInterface $marketHandler
    ): View {
        $amountToReceive = $this->donationHandler->checkDonation(
            $market,
            $currency,
            $amount,
            $this->getCurrentUser()
        );

        $sellOrdersWorth = $marketHandler->getSellOrdersWorth($market);
        $sellOrdersWorth = $this->donationHandler->getSellOrdersWorth($sellOrdersWorth, $currency);

        return $this->view([
            'amountToReceive' => $amountToReceive,
            'sellOrdersWorth' => $sellOrdersWorth,
        ]);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{base}/{quote}/make", name="make_donation", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="currency",
     *     allowBlank=false,
     *     requirements="(WEB|BTC)",
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
        $this->donationHandler->makeDonation(
            $market,
            $request->get('currency'),
            (string)$request->get('amount'),
            (string)$request->get('expected_count_to_receive'),
            $this->getCurrentUser()
        );

        return $this->view(null, Response::HTTP_ACCEPTED);
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
