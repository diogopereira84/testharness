<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Api\Data;

interface CustomerPunchoutUniqueIdInterface
{
    /**
     * String constants for property names
     */
    public const CUSTOMER_ID = "customer_id";
    public const CUSTOMER_EMAIL = "customer_email";
    public const UNIQUE_ID = "unique_id";

    /**
     * Getter for CustomerId.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Setter for CustomerId.
     *
     * @param int|null $customerId
     *
     * @return void
     */
    public function setCustomerId(?int $customerId): void;

    /**
     * Getter for CustomerEmail.
     *
     * @return string|null
     */
    public function getCustomerEmail(): ?string;

    /**
     * Setter for CustomerEmail.
     *
     * @param string|null $customerEmail
     *
     * @return void
     */
    public function setCustomerEmail(?string $customerEmail): void;

    /**
     * Getter for UniqueId.
     *
     * @return string|null
     */
    public function getUniqueId(): ?string;

    /**
     * Setter for UniqueId.
     *
     * @param string|null $uniqueId
     *
     * @return void
     */
    public function setUniqueId(?string $uniqueId): void;
}
