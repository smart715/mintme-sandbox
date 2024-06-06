<?php declare(strict_types = 1);

namespace App\Manager\Model;

use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class SendCodeDiffModel
{
    private bool $isSendCodeEnabled;
    private int $sendCodeDiff;
    private bool $isLimitReached;

    public function __construct(bool $isSendCodeEnabled, int $sendCodeDiff, bool $isLimitReached)
    {
        $this->isSendCodeEnabled = $isSendCodeEnabled;
        $this->sendCodeDiff = $sendCodeDiff;
        $this->isLimitReached = $isLimitReached;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function isSendCodeEnabled(): bool
    {
        return $this->isSendCodeEnabled;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function isLimitReached(): bool
    {
        return $this->isLimitReached;
    }

    /**
     * @Groups({"Default", "API"})
     */
    public function getSendCodeDiff(): int
    {
        return $this->sendCodeDiff;
    }
}
