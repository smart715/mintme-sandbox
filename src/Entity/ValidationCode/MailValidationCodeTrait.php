<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use Doctrine\Common\Collections\Criteria;

/** @codeCoverageIgnore */
trait MailValidationCodeTrait
{
    public function getMailCode(): ?ValidationCodeInterface
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('codeType', ValidationCode::TYPE_MAIL))
            ->setMaxResults(1);
        $mailCode = $this->getValidationCode()->matching($criteria)->first();

        return $mailCode ?: null;
    }

    public function setMailCode(ValidationCodeInterface $mailCode): self
    {
        $mailCode->setCodeType(ValidationCode::TYPE_MAIL);
        $this->addValidationCode($mailCode);

        return $this;
    }
}
