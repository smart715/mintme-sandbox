<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Exception\ApiBadRequestException;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/airdrop_campaign")
 */
class AirdropCampaignController extends AbstractFOSRestController
{
    /** @var TokenManagerInterface */
    private $tokenManager;

    /** @var AirdropCampaignManagerInterface */
    private $airdropCampaignManager;

    public function __construct(
        TokenManagerInterface $tokenManager,
        AirdropCampaignManagerInterface $airdropCampaignManager
    ) {
        $this->tokenManager = $tokenManager;
        $this->airdropCampaignManager = $airdropCampaignManager;
    }

    /**
     * @Rest\View()
     * @Rest\Get("/{tokenName}", name="get_airdrop_campaign", options={"expose"=true})
     */
    public function getAirdropCampaign(string $tokenName): View
    {
        $token = $this->fetchToken($tokenName);

        return $this->view($token->getActiveAirdrop(), Response::HTTP_OK);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/create", name="create_airdrop_campaign", options={"expose"=true})
     * @Rest\RequestParam(
     *     name="amount",
     *     allowBlank=false,
     *     description="Amount of tokens."
     * )
     * @Rest\RequestParam(
     *     name="participants",
     *     allowBlank=false,
     *     description="Amount of participants."
     * )
     * @Rest\RequestParam(
     *     name="endDate",
     *     allowBlank=true,
     *     nullable=true,
     *     description="Airdrop campaign end date timestamp."
     * )
     */
    public function createAirdropCampaign(
        string $tokenName,
        MoneyWrapperInterface $moneyWrapper,
        Request $request
    ): View {
        $token = $this->fetchToken($tokenName, true);
        $amount = $moneyWrapper->parse(
            (string)$request->get('amount'),
            MoneyWrapper::TOK_SYMBOL
        );
        $participants = (int)$request->get('participants');

        if ($amount->isNegative() || $amount->isZero()) {
            throw new \InvalidArgumentException('Incorrect amount.');
        }

        if (!$participants) {
            throw new \InvalidArgumentException('Incorrect participants amount.');
        }

        $endDate = $request->get('endDate')
            ? (new \DateTimeImmutable())->setTimestamp($request->get('endDate'))
            : null;

        $airdrop = $this->airdropCampaignManager->createAirdrop(
            $token,
            $amount,
            $participants,
            $endDate
        );

        return $this->view([
            'id' => $airdrop->getId(),
        ], Response::HTTP_ACCEPTED);
    }

    /**
     * @Rest\View()
     * @Rest\Delete(
     *     "/{id}/delete",
     *     name="delete_airdrop_campaign",
     *     options={"expose"=true},
     *     requirements={"id"="\d+"}
     * )
     */
    public function deleteAirdropCampaign(Airdrop $airdrop): View
    {
        $this->airdropCampaignManager->deleteAirdrop($airdrop);

        if ($airdrop->getToken() !== $this->tokenManager->getOwnToken()) {
            throw $this->createAccessDeniedException();
        }

        return $this->view(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Rest\View()
     * @Rest\Post("/{tokenName}/claim", name="claim_airdrop_campaign", options={"expose"=true})
     */
    public function claimAirdropCampaign(string $tokenName): View
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $token = $this->fetchToken($tokenName, false, true);

        if (!$token->getActiveAirdrop()) {
            throw new ApiBadRequestException('Token does not have active airdrop campaign.');
        }

        $this->airdropCampaignManager->claimAirdropCampaign(
            $this->getUser(),
            $token
        );

        return $this->view(null, Response::HTTP_ACCEPTED);
    }

    private function fetchToken(
        string $tokenName,
        bool $checkIfOwner = false,
        bool $checkIfParticipant = false
    ): Token {
        /** @var Token|null $token */
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token) {
            throw $this->createNotFoundException('Token does not exist.');
        }

        if ($checkIfOwner && $token !== $this->tokenManager->getOwnToken()) {
            throw $this->createAccessDeniedException();
        }

        if ($checkIfParticipant && $token === $this->tokenManager->getOwnToken()) {
            throw new ApiBadRequestException('Sorry, you can\'t participate in your own airdrop.');
        }

        return $token;
    }
}
