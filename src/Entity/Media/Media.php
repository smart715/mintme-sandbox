<?php

namespace App\Entity\Media;

use Sonata\MediaBundle\Entity\BaseMedia;
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;

class Media extends BaseMedia
{

    /** @var int $id */
    protected $id;

    /**
     * Get id
     *
     * @return int $id
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProviderStatus(): int
    {
        if (!$this->providerStatus) {
            $this->setProviderStatus($this::STATUS_ERROR);
        }

        return $this->providerStatus;
    }

    public function getProviderReference(): string
    {
        if (!$this->providerReference) {
            $this->setProviderReference(ProviderNotFoundException::class);
        }

        return $this->providerReference;
    }
}
