<?php
declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Model\DTO;

class FreightRequestDTO
{
    private array $data;

    public function __construct(string $jsonContent)
    {
        $this->data = json_decode($jsonContent, true) ?? [];
    }

    public function hasLiftGate(): bool
    {
        return (bool)($this->data['hasLiftGate'] ?? false);
    }
}
