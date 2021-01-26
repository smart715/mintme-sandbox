<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @codeCoverageIgnore
 * @ORM\Table(name="phone_number")
 * @ORM\HasLifecycleCallbacks()
 */
class PhoneNumber
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Profile", inversedBy="phoneNumber")
     */
    private int $profile;

    /**
     * @ORM\Column(type="phone_number")
     */
    private string $phoneNumber;

    public function getProfile(): int
    {
        return $this->profile;
    }

    public function setProfile(int $profile): self
    {
        $this->profile = $profile;

        return $this;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }
}
