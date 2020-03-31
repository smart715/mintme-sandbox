<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\AirdropCampaign\Airdrop;
use App\Entity\Token\Token;
use App\Manager\AirdropCampaignManagerInterface;
use App\Manager\TokenManagerInterface;
use App\Wallet\Money\MoneyWrapper;
use App\Wallet\Money\MoneyWrapperInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\Route("/api/airdrop_campaign")
 * @Security(expression="is_granted('prelaunch')")
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
            ? new \DateTimeImmutable($request->get('endDate'))
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
        $token = $this->fetchToken($tokenName);

        if (!$token->getActiveAirdrop()) {
            throw $this->createNotFoundException('Token does not have active airdrop campaign.');
        }

        $this->airdropCampaignManager->claimAirdropCampaign(
            $this->getUser(),
            $token
        );

        return $this->view(null, Response::HTTP_ACCEPTED);
    }

    private function fetchToken(string $tokenName, bool $checkAccess = false): Token
    {
        /** @var Token $token */
        $token = $this->tokenManager->findByName($tokenName);

        if (!$token instanceof Token) {
            throw $this->createNotFoundException('Token does not exist.');
        }

        if ($checkAccess && $token !== $this->tokenManager->getOwnToken()) {
            throw $this->createAccessDeniedException();
        }

        return $token;
    }
}
