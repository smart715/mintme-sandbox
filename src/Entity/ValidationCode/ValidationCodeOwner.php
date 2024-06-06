<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;

/** @codeCoverageIgnore */
abstract class ValidationCodeOwner implements ValidationCodeOwnerInterface
{
    public const CODE_LENGTH = 6;

    /**
     * @var ArrayCollection|PersistentCollection
     */
    protected Collection $validationCode;

    public function __construct()
    {
        $this->validationCode = new ArrayCollection();
    }
    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getValidationCode(): Collection
    {
        return $this->validationCode;
    }

    public function addValidationCode(ValidationCodeInterface $validationCode): self
    {
        if (!$this->validationCode->contains($validationCode)) {
            $this->validationCode->add($validationCode);
        }

        return $this;
    }

    public function applyOnValidationCodes(callable $callback): void
    {
        $codes = $this->getValidationCode()
            ->matching(Criteria::create());

        foreach ($codes as $code) {
            $callback($code);
        }
    }
}
