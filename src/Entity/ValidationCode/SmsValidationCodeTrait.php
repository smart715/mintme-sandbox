<?php declare(strict_types = 1);

namespace App\Entity\ValidationCode;

use Doctrine\Common\Collections\Criteria;

/** @codeCoverageIgnore */
trait SmsValidationCodeTrait
{
    public function getSMSCode(): ?ValidationCodeInterface
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('codeType', ValidationCode::TYPE_SMS))
            ->setMaxResults(1);
        $validationCode = $this->getValidationCode()->matching($criteria)->first();

        return $validationCode ?: null;
    }

    public function setSMSCode(ValidationCodeInterface $smsCode): self
    {
        $smsCode->setCodeType(ValidationCode::TYPE_SMS);
        $this->addValidationCode($smsCode);

        return $this;
    }
}
