<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Donation\DonationHandlerInterface;
use App\Exchange\Market;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/donation")
 * @Security(expression="is_granted('prelaunch')")
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
    public function checkDonation(Market $market, string $currency, string $amount): View
    {
        $amountToReceive = $this->donationHandler->checkDonation(
            $market,
            $currency,
            $amount,
            $this->getDonationFee()
        );

        return $this->view($amountToReceive);
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
            $this->getDonationFee(),
            (string)$request->get('expected_count_to_receive'),
            $this->getUser()
        );

        return $this->view(null, Response::HTTP_ACCEPTED);
    }

    private function getDonationFee(): string
    {
        return (string)$this->getParameter('donation')['fee'];
    }
}
