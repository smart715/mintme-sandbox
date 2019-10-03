<?php declare(strict_types = 1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ApiKeyRepository")
 * @codeCoverageIgnore
 */
class ApiKey
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="User", inversedBy="apiKey", cascade={"persist"})
     * @var User
     */
    protected $user;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $publicKey;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $privateKey;

    /** @var string|null */
    protected $plainPrivateKey;

    /** @Groups({"API"}) */
    public function getPlainPrivateKey(): string
    {
        return (string)$this->plainPrivateKey;
    }

    /** @Groups({"API"}) */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public static function fromNewUser(User $user): self
    {
        $key = new self();
        $key->user = $user;
        $key->publicKey = hash('sha256', Uuid::uuid4()->toString());
        $key->plainPrivateKey = hash('sha256', Uuid::uuid4()->toString());
        $key->privateKey = (string)password_hash($key->plainPrivateKey, PASSWORD_DEFAULT);

        return $key;
    }
}
