<?php

namespace App\Form\Model;

use App\Validator\Constraints\UserEmail;
use Symfony\Component\Validator\Constraints as Assert;

class EmailModel
{
    /**
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Email(
     *     message = "Invalid email address.",
     *     checkMX = true
     * )
     * @UserEmail()
     */
    private $email;

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
}
