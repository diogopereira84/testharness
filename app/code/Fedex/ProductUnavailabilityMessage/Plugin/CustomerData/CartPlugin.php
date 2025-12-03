<?php

/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\ProductUnavailabilityMessage\Plugin\CustomerData;

use Magento\Checkout\CustomerData\Cart;
use Fedex\ProductUnavailabilityMessage\ViewModel\CheckProductAvailability;
use Fedex\MarketplaceCheckout\Helper\Data as MarketPlaceHelper;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Psr\Log\LoggerInterface;

/**
 * Plugin Class CartPlugin
 */
class CartPlugin
{
    /**
     * @var MarketPlaceHelper
     */
    private $marketPlaceHelper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        private readonly CheckProductAvailability $checkProductAvailability,
        MarketPlaceHelper $marketPlaceHelper,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->marketPlaceHelper = $marketPlaceHelper;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
    }

    /**
     * Send config data to minicart
     *
     * @param  object $subject
     * @param  array $result
     * @return array|bool
     */
    public function afterGetSectionData(Cart $subject, $result)
    {
        if ($this->checkProductAvailability->isE441563ToggleEnabled()) {
            $result['unavailable_cart_msg']= $this->checkProductAvailability->getProductPDPErrorMessage();
            $result['isE441563ToggleEnabled']= $this->checkProductAvailability->isE441563ToggleEnabled();
        }
        $result['checkLegacyDocApiOnCartToggle'] = $this->marketPlaceHelper->checkLegacyDocApiOnCartToggle();
        $result['getCartItemExpiredMessage'] = $this->configProvider->getCartExpiredTitle();
        try {
            if ($result['checkLegacyDocApiOnCartToggle']) {
                $result['legacyDocumentStatus'] = $this->getLegacyDocumentStatus($result['items']);
            }
        } catch (\Exception $e) {
            $this->logger->error('An error occurred while checking legacy product for minicart: ' . $e->getMessage());
        }
        return $result;
    }

        /**
     * Get legacy document status for cart items.
     *
     * @param array $items
     * @return array
     */
    private function getLegacyDocumentStatus(array $items): array
    {
        $legacyDocumentStatus = [];

        foreach ($items as $item) {
            $legacyDocumentStatus[$item['item_id']] = false;

            $encodeProductAssociaton = json_encode($item['productContentAssociation']);
            $decodeProductAssociaton = json_decode($encodeProductAssociaton, true);

            if (isset($decodeProductAssociaton['contentAssociations']) && is_array($decodeProductAssociaton['contentAssociations'])) {
                foreach ($decodeProductAssociaton['contentAssociations'] as $association) {
                    if (isset($association['contentReference']) && is_numeric($association['contentReference'])) {
                        $legacyDocumentStatus[$item['item_id']] = true;
                        break;
                    }
                }
            }
        }

        return $legacyDocumentStatus;
    }
}
