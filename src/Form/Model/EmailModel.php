<?php

namespace App\Form\Model;

use App\Validator\Constraints\TwoFactorAuth;
use App\Validator\Constraints\UserEmail;
use Symfony\Component\Validator\Constraints as Assert;

class EmailModel
{
    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Email(
     *     mode="strict",
     *     message = "Invalid email address.",
     *     checkMX = true
     * )
     * @UserEmail()
     */
    private $email;

    /**
     * @var string|null
     * @TwoFactorAuth()
     */
    private $code;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return (string)$this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getCode(): string
    {
        return (string)$this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }
}
