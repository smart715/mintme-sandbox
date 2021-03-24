<?php declare(strict_types = 1);

namespace App\Manager\Model;

use Symfony\Component\Serializer\Annotation\Groups;

class SendCodeDiffModel
{
    private bool $isSendCodeEnabled;
    private int $sendCodeDiff;

    public function __construct(bool $isSendCodeEnabled, int $sendCodeDiff)
    {
        $this->isSendCodeEnabled = $isSendCodeEnabled;
        $this->sendCodeDiff = $sendCodeDiff;
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
    public function getSendCodeDiff(): int
    {
        return $this->sendCodeDiff;
    }
}
