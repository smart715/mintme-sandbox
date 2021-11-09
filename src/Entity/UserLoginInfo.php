<?php declare(strict_types = 1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserLoginInfoRepository")
 * @ORM\Table(name="user_login_info",
 *    uniqueConstraints={
 *        @ORM\UniqueConstraint(name="user_ip_unique",
 *            columns={"user_id", "ip_address"})
 *    }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @codeCoverageIgnore
 */
class UserLoginInfo
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=255)
     * @var String
     */
    private $ip_address;

    /**
     * @ORM\Column(type="string", length=255)
     * @var String
     */
    private $device_info;

    /**
     * @ORM\Column(type="string", length=255)
     * @var String
     */
    private $os_info;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @var DateTimeImmutable
     */
    protected $date;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getDeviceInfo(): ?string
    {
        return $this->device_info;
    }

    public function getOsInfo(): ?string
    {
        return $this->os_info;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }


    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }


    public function setIpAddress(string $ip_address): self
    {
        $this->ip_address = $ip_address;

        return $this;
    }

    public function setDeviceInfo(string $device_info): self
    {
        $this->device_info = $device_info;

        return $this;
    }

    public function setOsInfo(string $os_info): self
    {
        $this->os_info = $os_info;

        return $this;
    }

    public function setDate(DateTimeImmutable $Date): void
    {
        $this->date = $Date;
    }

    /** @ORM\PrePersist() */
    public function onAdd(): void
    {
        $this->setDate(new DateTimeImmutable());
    }
}
