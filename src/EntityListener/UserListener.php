<?php declare(strict_types = 1);

namespace App\EntityListener;

use App\Entity\User;
use App\Manager\OrderManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

class UserListener
{
    private OrderManagerInterface $orderManager;

    public function __construct(OrderManagerInterface $orderManager)
    {
        $this->orderManager = $orderManager;
    }

    public function postUpdate(User $user): void
    {
        if (!$user->isEnabled()) {
            $this->orderManager->deleteOrdersByUser($user);
        }
    }

    public function preUpdate(User $user, LifecycleEventArgs $args): void
    {
        $enabled = $user->isEnabled();
        $isBlocked = $user->isBlocked();

        $changedColumns = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($args->getEntity());

        if (isset($changedColumns['enabled'])) {
            $user->setIsBlocked(!$enabled);
        } elseif (isset($changedColumns['is_blocked'])) {
            $user->setEnabled(!$isBlocked);
        }

        if (isset($changedColumns['roles'])) {
            [$oldRoles, $newRoles] = $changedColumns['roles'];

            if (in_array(User::ROLE_SEMI_AUTHENTICATED, $oldRoles)) {
                if (!array_intersect([User::ROLE_SEMI_AUTHENTICATED, User::ROLE_AUTHENTICATED], $newRoles)) {
                    $user->addRole(User::ROLE_SEMI_AUTHENTICATED);
                }
            } elseif (in_array(User::ROLE_AUTHENTICATED, $oldRoles)) {
                if (!array_intersect([User::ROLE_SEMI_AUTHENTICATED, User::ROLE_AUTHENTICATED], $newRoles)) {
                    $user->addRole(User::ROLE_AUTHENTICATED);
                }
            }
        }
    }
}
