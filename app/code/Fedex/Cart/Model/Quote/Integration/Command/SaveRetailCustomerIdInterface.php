<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira <eoliveira@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\Integration\Command;

use Fedex\Cart\Api\Data\CartIntegrationInterface;

interface SaveRetailCustomerIdInterface
{
    /**
     * Save Retail Customer Id.
     *
     * @param CartIntegrationInterface $integration
     * @param string|null $retailCustomerId
     * @return void
     */
    public function execute(CartIntegrationInterface $integration, ?string $retailCustomerId): void;
}
