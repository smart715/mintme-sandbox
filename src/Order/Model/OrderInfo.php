<?php

namespace App\Order\Model;

use App\Entity\User;
use App\Exchange\Order;

class OrderInfo implements OrderInfoInterface
{
    /** @var Order */
    private $order;

    /** @var User */
    private $makerUser;

    /** @var User|null */
    private $takerUser;

    /** @var User|null */
    private $currentUser;

    public function __construct(Order $order, User $makerUser, ?User $takerUser, ?User $currentUser)
    {
        $this->order = $order;
        $this->makerUser = $makerUser;
        $this->takerUser = $takerUser;
        $this->currentUser = $currentUser;
    }

    public function getMakerFirstName(): ?string
    {
        return $this->order->getMakerId()
            ? $this->makerUser->getProfile()->getFirstName()
            : null;
    }

    public function getMakerLastName(): ?string
    {
        return $this->order->getMakerId()
            ? $this->makerUser->getProfile()->getLastName()
            : null;
    }

    public function getMakerProfileUrl(): ?string
    {
        return $this->order->getMakerId()
            ? $this->makerUser->getProfile()->getPageUrl()
            : null;
    }

    public function getTakerFirstName(): ?string
    {
        return $this->order->getTakerId() && $this->takerUser
            ? $this->takerUser->getProfile()->getFirstName()
            : null;
    }

    public function getTakerLastName(): ?string
    {
        return $this->order->getTakerId() && $this->takerUser
            ? $this->takerUser->getProfile()->getLastName()
            : null;
    }

    public function getTakerProfileUrl(): ?string
    {
        return $this->order->getTakerId() && $this->takerUser
            ? $this->takerUser->getProfile()->getPageUrl()
            : null;
    }

    public function getAmount(): float
    {
        return floatval($this->order->getAmount());
    }

    public function getPrice(): float
    {
        return floatval($this->order->getPrice());
    }

    public function getTotal(): float
    {
        return $this->getAmount() * $this->getPrice();
    }

    public function makerIsOwner(): bool
    {
        return $this->currentUser && ($this->order->getMakerId() === $this->currentUser->getId());
    }

    public function getSide(): int
    {
        return $this->order->getSide();
    }

    public function getTimestamp(): ?int
    {
        return $this->order->getTimestamp();
    }
}
