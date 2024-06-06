<?php declare(strict_types = 1);

namespace App\Admin;

use App\Entity\Classification\Context;
use Sonata\ClassificationBundle\Admin\CategoryAdmin as Admin;

/**
 * @codeCoverageIgnore
 * @phpstan-ignore-next-line marked as final class
 */
class CategoryAdmin extends Admin
{

    /**
     * @param mixed|object $object
     */
    public function prePersist($object): void
    {
        if (null == $object->getContext()) {
            $contextId = Context::DEFAULT_CONTEXT;
            $context = $this->contextManager->find($contextId);

            if (null == $context) {
                $context = new Context();
                $context->setId($contextId);
                $context->setName($contextId);
                $context->setEnabled(true);
                $this->contextManager->save($context);
            }

            $object->setContext($context);
        }

        parent::prePersist($object);
    }
}
