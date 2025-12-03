<?php

declare(strict_types=1);

namespace Fedex\Delivery\Model\Shipping;

class ShippingMethod
{
    private bool $isCheapest = false;
    private bool $isFastest = false;

    public function __construct(
        private readonly string $identityGroup,
        private readonly string $methodCode,
        private readonly float $amount,
        private readonly ?int $deliveryDate
    ) {}

    public function getIdentityGroup(): string
    {
        return $this->identityGroup;
    }

    public function getMethodCode(): string
    {
        return $this->methodCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDeliveryDate(): ?int
    {
        return $this->deliveryDate;
    }

    public function setCheapest(bool $value): void
    {
        $this->isCheapest = $value;
    }

    public function setFastest(bool $value): void
    {
        $this->isFastest = $value;
    }

    public function isCheapest(): bool
    {
        return $this->isCheapest;
    }

    public function isFastest(): bool
    {
        return $this->isFastest;
    }
}
