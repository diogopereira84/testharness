<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote;

use Fedex\Orderhistory\Helper\Data as orderHistoryHelper;
use Fedex\Shipto\Helper\Data;
use \Magento\Framework\App\Request\Http;
use Psr\Log\LoggerInterface;

class Info
{
    /**
     * @inheritDoc
     */
    public function __construct(
        protected orderHistoryHelper $helper,
        private Data $shipToHelper,
        private Http $request,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function afterGetAddressHtml(
        \Magento\NegotiableQuote\Block\Quote\Info $block,
        $result
    ) {

        $cureentQuoteAddress = $block->getQuote()->getBillingAddress();

        if ($this->helper->isModuleEnabled()
            && $cureentQuoteAddress->getShippingMethod() == 'fedexshipping_PICKUP'
            && $cureentQuoteAddress->getShippingDescription()
        ) {

            try {
                $pickupInfo = [];
                $customerPickupAddress = $cureentQuoteAddress->getPickupAddress();
                $pickupAddress  = $this->getPickupAddress($customerPickupAddress, $cureentQuoteAddress);

                if ($pickupAddress) {
                    $addressArray = json_decode($pickupAddress, true);

                    $pickupInfo['address'] = isset($addressArray['address']) ? $addressArray['address'] : [];
                    $pickupInfo['name'] = isset($addressArray['name']) ? $addressArray['name'] : '';
                    $pickupInfo['phone'] = isset($addressArray['phone']) ? $addressArray['phone'] : '';

                    $result = $this->shipToHelper->formatAddress($pickupInfo);
                }
            } catch (\Exception $e) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
                return $result;
            }
        }
        return $result;
    }
    /**
     * getPickupAddress
     * @param pickupAddress $pickupAddress
     * @param cureentQuoteAddress $cureentQuoteAddress
     * return array|string
     */
    public function getPickupAddress($pickupAddress, $cureentQuoteAddress)
    {
        if (!$pickupAddress) {
            $locationId = $cureentQuoteAddress->getShippingDescription();
            $addressFromApi = $this->shipToHelper->getAddressByLocationId($locationId);
            if (isset($addressFromApi['success']) && $addressFromApi['success'] == 1) {
               $pickupAddress = $addressFromApi['address'];
            }
        }
        return $pickupAddress;
    }
}
