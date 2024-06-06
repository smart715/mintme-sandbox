<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use Doctrine\Common\Collections\Collection;

interface ValidationCodeOwnerInterface
{
    public function getValidationCode(): Collection;
    public function addValidationCode(ValidationCodeInterface $validationCode): self;
    public function applyOnValidationCodes(callable $callback): void;
}
