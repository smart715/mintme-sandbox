<?php declare(strict_types = 1);

namespace App\Manager;

use App\Activity\ActivityTypes;
use App\Entity\Activity;
use App\Repository\Activity\ActivityRepository;

/**
 * @codeCoverageIgnore
 */
class ActivityManager implements ActivityManagerInterface
{
    private ActivityRepository $activityRepository;

    public function __construct(ActivityRepository $activityRepository)
    {
        $this->activityRepository = $activityRepository;
    }

    /** @inheritDoc */
    public function getLast(int $limit): array
    {
        $activities = [];
        $offset = 0;

        while ($this->countPostGrouping($activities) < $limit) {
            $newActivities = $this->activityRepository->getUniqueLast($offset, $limit);

            foreach ($newActivities as $activity) {
                $activities[] = $activity;
            }

            if (count($newActivities) < $limit) {
                break;
            }

            $offset += $limit;
        }

        return $activities;
    }

    /**
     * @param Activity[] $activities
     * @return int
     */
    private function countPostGrouping(array $activities): int
    {
        $length = 0;
        $pointer = '';

        foreach ($activities as $activity) {
            $type = $activity->getType();

            if (!in_array($type, [ActivityTypes::TOKEN_TRADED, ActivityTypes::DONATION])) {
                $length++;
                $pointer = '';

                continue;
            }

            if ($pointer !== $this->generateGroupedKey($type, $activity->getContext())) {
                $pointer = $this->generateGroupedKey($type, $activity->getContext());
                $length++;
            }
        }

        return $length;
    }

    private function generateGroupedKey(int $type, array $context): string
    {
        $tokenName = $context['token'] ?? null;
        $currency = $context['symbol'] ?? null;
        $buyerId = $context['buyer'] ?? null;

        return ActivityTypes::DONATION === $type
            ? $tokenName . $currency
            : $tokenName . $currency . $buyerId;
    }
}
