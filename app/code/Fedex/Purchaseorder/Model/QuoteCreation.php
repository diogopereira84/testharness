<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Purchaseorder\Model;

use Magento\Checkout\Model\ShippingInformationManagement;

/**
 * QuoteCreation Model
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class QuoteCreation
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**

     * @param \Magento\Checkout\Model\ShippingInformationManagement $shippingInformationManagement
     * @param \Magento\Quote\Api\Data\AddressInterface $addressInterface
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformationInterface
     *
     */
    public function __construct(
        private ShippingInformationManagement $shippingInformationManagement,
        private \Magento\Quote\Api\Data\AddressInterface $addressInterface,
        private \Magento\Checkout\Api\Data\ShippingInformationInterface $shippingInformationInterface,
        protected \Psr\Log\LoggerInterface $logger
    )
    {
    }

    /**
     * Save Shipping Address
     *
     * @param array     $shippingInfo
     * @param int       $cartId/$quoteId
     * @return string|array|boolean
     */
    public function saveShippingAddress($shippingInfo, $cartId)
    {
        if (isset($shippingInfo['addressInformation']['shipping_address'])
            && $shippingInfo['addressInformation']['shipping_method_code']
                && $shippingInfo['addressInformation']['shipping_carrier_code']) {

            $shippingData = $shippingInfo['addressInformation']['shipping_address'];

            $methodCode = $shippingInfo['addressInformation']['shipping_method_code'];
            $carrierCode = $shippingInfo['addressInformation']['shipping_carrier_code'];

            if (isset($shippingData['region']) && $shippingData['region_id']
                && $shippingData['country_id'] && $shippingData['street']
                && $shippingData['postcode'] && $shippingData['city']
                && $shippingData['telephone'] && $shippingData['firstname']
                && $shippingData['lastname'] && $shippingData['email']
            ) {

                $this->addressInterface->setRegion($shippingData['region']);
                $this->addressInterface->setRegionId($shippingData['region_id']);
                $this->addressInterface->setCountryId($shippingData['country_id']);
                $this->addressInterface->setStreet($shippingData['street']);
                $this->addressInterface->setPostcode($shippingData['postcode']);
                $this->addressInterface->setCity($shippingData['city']);
                $this->addressInterface->setCompany($shippingData['company']);
                $this->addressInterface->setTelephone($shippingData['telephone']);
                $this->addressInterface->setFirstname($shippingData['firstname']);
                $this->addressInterface->setLastname($shippingData['lastname']);
                $this->addressInterface->setEmail($shippingData['email']);

                $this->shippingInformationInterface->setShippingAddress($this->addressInterface);
                $this->shippingInformationInterface->setBillingAddress($this->addressInterface);
                $this->shippingInformationInterface->setShippingMethodCode($methodCode);
                $this->shippingInformationInterface->setShippingCarrierCode($carrierCode);

                $response = $this->shippingInformationManagement
                                ->saveAddressInformation($cartId, $this->shippingInformationInterface);
                $this->logger->info(__METHOD__.":".__LINE__." Saving Shipping Address Successful");
                return "success";

            }

        }
        $this->logger->error(__METHOD__.":".__LINE__." Saving Shipping Address UNSUCCESSFUL");
        return false;
    }
}
