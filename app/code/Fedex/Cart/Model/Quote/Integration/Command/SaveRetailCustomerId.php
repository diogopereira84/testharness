<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Oliveira <eoliveira@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote\Integration\Command;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class SaveRetailCustomerId implements SaveRetailCustomerIdInterface
{
    /**
     * SaveRetailCustomerId constructor
     *
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(CartIntegrationInterface $integration, ?string $retailCustomerId): void
    {
        if (empty($retailCustomerId)) {
            $retailCustomerId = null;
        }

        try {
            $integration->setRetailCustomerId($retailCustomerId);
            $this->cartIntegrationRepository->save($integration);
        } catch (CouldNotSaveException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' Error on saving data on quote integration. ' . $e->getMessage()
            );
        }
    }
}
