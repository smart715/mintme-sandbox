<?php declare(strict_types = 1);

namespace App\Controller\API;

use App\Entity\User;
use App\Manager\PromotionHistory\PromotionHistoryManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @Rest\Route("/api/promotion")
 */

class PromotionHistoryController extends AbstractFOSRestController
{
    private const WALLET_ITEMS_BATCH_SIZE = 11;

    private PromotionHistoryManagerInterface $promotionHistoryManager;
    private NormalizerInterface $normalizer;

    public function __construct(
        PromotionHistoryManagerInterface $promotionHistoryManager,
        NormalizerInterface $normalizer
    ) {
        $this->promotionHistoryManager = $promotionHistoryManager;
        $this->normalizer = $normalizer;
    }

    /**
     * @Rest\View()
     * @Rest\Get(
     *     "/history/{page}/",
     *     name="promotion_history",
     *     requirements={"page"="^[0-9]\d*$"},
     *     defaults={"page"=1},
     *     options={"expose"=true}
     *     )
     */
    public function getPromotionHistory(
        int $page
    ): View {
        /** @var User $user*/
        $user = $this->getUser();

        $offset = ($page - 1) * self::WALLET_ITEMS_BATCH_SIZE;

        $history = $this->promotionHistoryManager->getPromotionHistory(
            $user,
            $offset,
            self::WALLET_ITEMS_BATCH_SIZE
        );

        return $this->view($this->normalizer->normalize($history, null, [
            'groups' => ['API', 'PROMOTION_HISTORY'],
        ]));
    }
}
