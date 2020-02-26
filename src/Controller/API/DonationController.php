<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Exchange\Donation\DonationHandlerInterface;
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
     * @Rest\Get("/check/{market}/{amount}/{fee}", name="check_donation", options={"expose"=true})
     * @Rest\RequestParam(name="market", allowBlank=false, requirements="(WEB|BTC)", description="Market name.")
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(name="fee", allowBlank=false, description="Donation fee.")
     */
    public function checkDonation(string $market, string $amount, string $fee): View
    {
        $amountToReceive = 10;
//        $amountToReceive = $this->donationHandler->checkDonation(
//            $market,
//            $amount,
//            $fee
//        );

        return $this->view($amountToReceive);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/make", name="make_donation", options={"expose"=true})
     * @Rest\RequestParam(name="market", allowBlank=false, requirements="(WEB|BTC)", description="Market name.")
     * @Rest\RequestParam(name="amount", allowBlank=false)
     * @Rest\RequestParam(name="fee", allowBlank=false, description="Donation fee.")
     * @Rest\RequestParam(name="expected_count_to_receive", allowBlank=false, description="Expected tokens count to receive.")
     */
    public function getBalance(ParamFetcherInterface $request): View
    {
//        $this->donationHandler->makeDonation(
//            (string)$request->get('market'),
//            (string)$request->get('amount'),
//            (string)$request->get('fee'),
//            (string)$request->get('expected_count_to_receive')
//        );

        return $this->view(null, Response::HTTP_ACCEPTED);
    }
}
