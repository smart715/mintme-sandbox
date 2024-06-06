<?php declare(strict_types = 1);

namespace App\Activity\Factory;

use App\Activity\ActivityHelper;
use App\Entity\Activity;
use App\Events\Activity\ActivityEventInterface;
use App\Wallet\Money\MoneyWrapperInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractActivityCreator
{
    protected ActivityHelper $activityHelper;
    protected RouterInterface $router;
    protected MoneyWrapperInterface $moneyWrapper;

    public function __construct(
        ActivityHelper $activityHelper,
        RouterInterface $router,
        MoneyWrapperInterface $moneyWrapper
    ) {
        $this->activityHelper = $activityHelper;
        $this->router = $router;
        $this->moneyWrapper = $moneyWrapper;
    }

    abstract public function create(ActivityEventInterface $event): Activity;
}
