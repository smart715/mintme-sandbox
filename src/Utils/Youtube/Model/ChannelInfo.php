<?php declare(strict_types = 1);

namespace App\Utils\Youtube\Model;

use Symfony\Component\Serializer\Annotation\Groups;

/** @codeCoverageIgnore */
class ChannelInfo
{
    private string $img;
    private string $name;
    private string $description;

    public function __construct(
        string $img,
        string $name,
        string $description
    ) {
        $this->img = $img;
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * @Groups({"API"})
     */
    public function getImg(): string
    {
        return $this->img;
    }

    /**
     * @Groups({"API"})
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @Groups({"API"})
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}
