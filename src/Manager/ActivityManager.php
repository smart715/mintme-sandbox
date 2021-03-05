<?php declare(strict_types = 1);

namespace App\Manager;

use App\Entity\Activity\Activity;
use App\Repository\ActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;

class ActivityManager implements ActivityManagerInterface
{
    private EntityManagerInterface $entityManager;
    private ActivityRepository $activityRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivityRepository $activityRepository
    ) {
        $this->entityManager = $entityManager;
        $this->activityRepository = $activityRepository;
    }

    public function getLast(int $limit): array
    {
        $activities = $this->activityRepository->findBy([], ['createdAt' => 'DESC'], $limit);

        $defaultPropertyCount = count((new ReflectionClass(Activity::class))->getProperties());

        // Since we find Activity-es, they only bring the properties from Activity. To get the subclass properties, we need to refresh them
        // The reflections are to only refresh the ones with more properties than the Activity class (faster)
        foreach ($activities as $activity) {
            $propertyCount = count((new ReflectionClass($activity))->getProperties());

            if ($propertyCount > $defaultPropertyCount) {
                $this->entityManager->refresh($activity);
            }
        }

        return $activities;
    }
}
