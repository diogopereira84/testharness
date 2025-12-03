<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Address;

use Fedex\B2b\Model\Quote\Address;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\CartGraphQl\Api\CollectRatesInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CollectRates implements CollectRatesInterface
{
    /**
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param Builder $addressBuilder
     * @param LoggerInterface $logger
     * @param array $collectRates
     */
    public function __construct(
        private CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private Builder $addressBuilder,
        protected LoggerInterface $logger,
        private array $collectRates = []
    ) {
    }

    /**
     * @param Address $address
     * @return void
     */
    public function execute(Address $address): void
    {
        try {
            $integration = $this->cartIntegrationRepository->getByQuoteId($address->getQuote()->getId());
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ .
                'Error in Fetching Quote Integration: ' . $e->getMessage()
            );
            $this->addressBuilder->setShippingData($address->getQuote(), $address);
            return;
        }

        if (!$integration->getDeliveryData()) {
            $this->addressBuilder->setShippingData($address->getQuote(), $address);
            return;
        }

        foreach ($this->collectRates as $collectRate) {
            $collectRate->collect($address, $integration);
        }
    }
}
