<?php
declare(strict_types=1);

namespace Fedex\LateOrdersGraphQl\Model\Data;

use Fedex\LateOrdersGraphQl\Api\Data\CustomerDTOInterface;

class CustomerDTO implements CustomerDTOInterface
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone
    ) {}

    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
}
