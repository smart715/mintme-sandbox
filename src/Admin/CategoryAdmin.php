<?php declare(strict_types = 1);

namespace App\Admin;

use App\Entity\Classification\Context;
use Sonata\ClassificationBundle\Admin\CategoryAdmin as Admin;

class CategoryAdmin extends Admin
{

    /** @inheritdoc */
    public function prePersist($object)
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
