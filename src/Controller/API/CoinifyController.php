<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Communications\CoinifyCommunicator;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;

/**
 * @Rest\Route("/api/coinify")
 */
class CoinifyController extends AbstractFOSRestController
{
    /** @var CoinifyCommunicator */
    private $coinifyCommunicator;

    /** @var EntityManagerInterface */
    private $em;

    public function __construct(
        CoinifyCommunicator $coinifyCommunicator,
        EntityManagerInterface $em
    ) {
        $this->coinifyCommunicator = $coinifyCommunicator;
        $this->em = $em;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/refresh-token",
     *     name="refresh_token",
     *     options={"expose"=true}
     *     )
     * @return string
     */
    public function getDepositWithdrawHistory(): string
    {
        /** @var User $user*/
        $user = $this->getUser();

        if (!$user->getCoinifyOfflineToken()) {
            $coinifyOfflineToken = $this->coinifyCommunicator->signupTrader($user);

            $user->setCoinifyOfflineToken($coinifyOfflineToken);
            $this->em->persist($user);
            $this->em->flush();
        }

        return $this->coinifyCommunicator->getRefreshToken($user);
    }
}
