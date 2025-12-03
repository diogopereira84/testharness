<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Plugin\Frontend\Magento\Sales\Block\Order;

class Info
{
    /**
     * @inheritDoc
     */
    public function __construct(
        protected \Fedex\Orderhistory\Helper\Data $helper,
        protected \Fedex\Shipto\Helper\Data $shipToHelper,
        protected \Magento\Framework\App\Request\Http $request
    )
    {
    }

    /**
     * @inheritDoc
     *
     * B-1078869/B-1082825 | Display pickup location from DB on orders and quotes
     */
    public function afterGetFormattedAddress(
        \Magento\Sales\Block\Order\Info $subject,
        $result,
        $address
    ) {
        $fullaction = $this->request->getFullActionName();
        $actions = ['sales_order_view', 'sales_order_print'];
        $currentOrder = $subject->getOrder();
        
        if (($this->helper->isModuleEnabled() == true || $this->helper->isPrintReceiptRetail() == true )
            && in_array($fullaction, $actions)
                && $currentOrder->getShippingMethod() == 'fedexshipping_PICKUP'
                    && $address->getAddressType() == 'shipping'
                    && $currentOrder->getShippingDescription()
        ) {
            $pickupInfo = [];
            $pickupAddress = $currentOrder->getPickupAddress();
            if (!$pickupAddress) {
                $pickupAddress = $this->getPickupAddressFromCurrentOrder($currentOrder);
            }

            if ($pickupAddress) {
                $pickupInfo = $this->getPickupInfoFromAddress($pickupAddress);
                $result = $this->helper->formatAddress($pickupInfo);
                return $result;
            }
        }

        /** B-1149275 - View Order Receipt - Delivery */
        if (($this->helper->isModuleEnabled() == true || $this->helper->isPrintReceiptRetail() == true )
            && in_array($fullaction, $actions)
            && $address->getAddressType() == 'shipping'
            && $currentOrder->getShippingAddress()
        ) {
            $shippingAddressData = $this->prepareShippingAddressData($currentOrder);
            $result = $this->helper->formatAddress($shippingAddressData);
        }

        /** B-1149275 - View Order Receipt - Delivery */
        if (($this->helper->isModuleEnabled() == true || $this->helper->isPrintReceiptRetail() == true)
            && in_array($fullaction, $actions)
            && $address->getAddressType() == 'billing'
            && $currentOrder->getBillingAddress()
        ) {
            $billingAddressData = $this->prepareBillingAddressData($currentOrder);
            $result = $this->helper->formatAddress($billingAddressData);
        }
        
        return $result;
    }

    public function getPickupAddressFromCurrentOrder($currentOrder)
    {
        $pickupAddress = null;
        $locationId = $currentOrder->getShippingDescription();
        $addressFromApi = $this->shipToHelper->getAddressByLocationId($locationId);
        if (isset($addressFromApi['success']) && $addressFromApi['success'] == 1) {
            $pickupAddress = $addressFromApi['address'];
        }
        return $pickupAddress;
    }
    public function getPickupInfoFromAddress($pickupAddress)
    {
        $pickupInfo = [];
        $addressArray = json_decode($pickupAddress, true);
        $pickupInfo['name'] = isset($addressArray['name']) ? $addressArray['name'] : '';
        $streetAddress = $addressArray['address']['address1'] . ' '. $addressArray['address']['address2'];
        $pickupInfo['address']['street'] = $streetAddress;
        $pickupInfo['address']['city'] = $addressArray['address']['city'];
        $pickupInfo['address']['stateOrProvinceCode'] = $addressArray['address']['stateOrProvinceCode'] ?? '';
        $pickupInfo['address']['postalCode'] = $addressArray['address']['postalCode'];
        if ($this->helper->isEnhancementEnabeled() || $this->helper->isPrintReceiptRetail() == true) {
            unset($pickupInfo['phone']);
        } else {
            $pickupInfo['phone'] = isset($addressArray['phone']) ? $addressArray['phone'] : '';
        }
        return $pickupInfo;
    }

    public function prepareShippingAddressData($currentOrder)
    {
        $shippingAddressData = [];
        $shippingAddress = $currentOrder->getShippingAddress()->getData();
        
        $firstname = isset($shippingAddress['firstname']) ? $shippingAddress['firstname'] : '';
        $lastname = isset($shippingAddress['lastname']) ? $shippingAddress['lastname'] : '';
        $shippingAddressData['name'] = $firstname.' '.$lastname;
        $shippingAddressData['address']['street'] = isset($shippingAddress['street'])
            ? $shippingAddress['street'] : '';
        $shippingAddressData['address']['city'] = isset($shippingAddress['city'])
            ? $shippingAddress['city'] : '';
        $shippingAddressData['address']['region'] = isset($shippingAddress['region'])
            ? $shippingAddress['region'] : '';
        $shippingAddressData['address']['postalCode'] = isset($shippingAddress['postcode'])
            ? $shippingAddress['postcode'] : '';
        
        $shippingAddress = $this->helper->getQuoteById($currentOrder->getQuoteId())->getShippingAddress();
        $shippingAddressData['email'] = $shippingAddress->getEmail() ?? '';
        
        $shippingAddressData['phone']= isset($shippingAddress['telephone']) ? $shippingAddress['telephone'] : '';
        
        return $shippingAddressData;
    }

    public function prepareBillingAddressData($currentOrder)
    {
        $billingAddressData = [];
        $billingAddress = $currentOrder->getBillingAddress()->getData();
        
        //B-1326759: Do not show billing address name when site configured CC is used
        if (
            $this->helper->isPrintReceiptRetail()
            && !$currentOrder->getPayment()->getSiteConfiguredPaymentUsed()
        ) {
            $firstname = $billingAddress['firstname'] ?? '';
            $lastname = $billingAddress['lastname'] ?? '';
            $billingAddressData['name'] = $firstname . ' ' . $lastname;
        }
        $billingAddressData['address']['street'] = isset($billingAddress['street']) ? $billingAddress['street'] : '';
        $billingAddressData['address']['city'] = isset($billingAddress['city']) ? $billingAddress['city'] : '';
        $billingAddressData['address']['region'] = isset($billingAddress['region']) ? $billingAddress['region'] : '';
        $billingAddressData['address']['postalCode'] = isset($billingAddress['postcode'])
            ? $billingAddress['postcode'] : '';
        return $billingAddressData;
    }
}
