<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOPricing\Model;

use Psr\Log\LoggerInterface;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;

class FXORequestBuilder
{
    /**
     * @param LoggerInterface $logger
     * @param DeliveryHelper $deliveryHelper
     * @param CompanyHelper $companyHelper
     * @param PunchoutHelper $punchoutHelper
     * @param CheckoutSession $checkoutSession
     * @param ToggleConfig $toggleConfig
     * @param QuoteHelper $quoteHelper
     * @param ShopManagementInterface $shopManagement
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private LoggerInterface $logger,
        private DeliveryHelper $deliveryHelper,
        private CompanyHelper $companyHelper,
        private PunchoutHelper $punchoutHelper,
        private CheckoutSession $checkoutSession,
        private ToggleConfig $toggleConfig,
        Private QuoteHelper $quoteHelper,
        Private ShopManagementInterface $shopManagement,
        Private CollectionFactory $collectionFactory
    )
    {
    }

    /**
     * Get authentication details
     *
     * @param Object $quote
     * @return array
     */
    public function getAuthenticationDetails($quote, $cartDataHelper)
    {
        $site = $siteName = $fedExAccountNumber = null;
        if ($this->deliveryHelper->isCommercialCustomer()) {
            $site = $this->deliveryHelper->getCompanySite();
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
            $access = $this->deliveryHelper->getApiToken();
            $accessToken = $access['token'];
            // B-1250149 : Magento Admin UI changes to group all the Customer account details
            $fedExAccountNumber = $this->companyHelper->getFedexAccountNumber();
            //B-1275215: Autopopulate fedex account number in cart
            if (
                $fedExAccountNumber
                && !$this->checkoutSession->getRemoveFedexAccountNumber()
            ) {
                $fedExAccountNumber = $cartDataHelper->encryptData($fedExAccountNumber);
                $quote->setData('fedex_account_number', $fedExAccountNumber);
            }
        } else {
            $gateWayToken = $this->punchoutHelper->getAuthGatewayToken();
            $accessToken = $this->punchoutHelper->getTazToken();
        }
        //B-1946193 Document Level Routing - RateQuote siteName Logic and Testing
        if (
            $this->companyHelper->getCustomerCompany()
        ) {
            if($this->companyHelper->getCustomerCompany()->getSiteName() &&
                $this->companyHelper->getCustomerCompany()->getSiteName() != NULL)
            {
                $siteName = null;
                $site = $this->companyHelper->getCustomerCompany()->getSiteName();
            }
            else{
                $siteName = $this->companyHelper->getCustomerCompany()->getCompanyName() ?? null;
                $site = null;
            }
        }
        //B-1946193 Document Level Routing - RateQuote siteName Logic and Testing



        $fedExAccountNumber = $cartDataHelper->decryptData($quote->getData("fedex_account_number")) ?? null;

        return [
            'gateWayToken' => $gateWayToken,
            'accessToken' => $accessToken,
            'fedexAccountNumber' => $fedExAccountNumber,
            'site' => $site,
            'siteName' => $siteName
        ];
    }

    /**
     * Get Shipping/Pickup Data
     */
    public function getPickShipData($quote, $itemData)
    {
        $arrRecipientsData = [];
        $arrRecipientsData['fedExAccountNumber'] = null;
        $arrRecipientsData['arrRecipients'] = null;

        $referenceId = $this->getShipmentId($quote);

        $arrShippingAddress = $quote->getCustomerShippingAddress() ?? null;
        $arrPickupLocationdata = $quote->getCustomerPickupLocationData() ?? null;

        $marketPlaceShippingMethodCode = $marketPlaceShippingMethodPrice = $marketPlaceShippingDeliveryDate = '';

        $isMixedCart = false;
        if ($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote)) {
            $isMixedCart = true;
        }

        // Set variables if Mirakl quote for future use
        if ($isMixedCart || $this->quoteHelper->isFullMiraklQuote($quote)) {
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                if ($item->getMiraklOfferId()) {
                    $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                    if (isset($additionalData['mirakl_shipping_data'])) {
                        $shippingData = $additionalData['mirakl_shipping_data'];
                        $marketPlaceShippingMethodCode = $shippingData['method_code'];
                        $marketPlaceShippingMethodPrice = $shippingData['amount'];
                        $marketPlaceShippingDeliveryDate = $shippingData['deliveryDate'];
                        break;
                    }
                }
            }
        }

        // Pickup
        if ($quote->getIsFromPickup() && is_array($arrPickupLocationdata) && $arrPickupLocationdata['locationId']
            && $arrPickupLocationdata['locationId'] != "null") {
            if ($isMixedCart && !empty($marketPlaceShippingMethodCode) && is_array($arrShippingAddress)
                || (!$this->quoteHelper->isMiraklQuote($quote))) {

                $arrRecipientsData['arrRecipients'] = [
                    0 => [
                        'reference' => $referenceId,
                        'contact' => null,
                        'pickUpDelivery' => [
                            'location' => [
                                'id' => $arrPickupLocationdata['locationId'],
                            ],
                            'requestedPickupLocalTime' => !empty($quote->getData('requestedPickupDateTime')) ?
                                $quote->getData('requestedPickupDateTime') : null,
                        ],
                        'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'], false)
                    ],
                ];

                if (isset($arrPickupLocationdata['fedExAccountNumber'])) {
                    $arrRecipientsData['fedExAccountNumber'] = $arrPickupLocationdata['fedExAccountNumber'];
                }
            }
        }

        // Shipping
        if ($quote->getIsFromShipping() && is_array($arrShippingAddress)) {
            if (($isMixedCart && !empty($arrShippingAddress['shipMethod'] && !empty($marketPlaceShippingMethodCode)))
                || (!$this->quoteHelper->isMiraklQuote($quote) && !empty($arrShippingAddress['shipMethod']))) {

                // Get ShipmentId
                $arrRecipientsData['arrRecipients'] = [
                    0 => [
                        'contact' => null,
                        'reference' => $referenceId,
                        'shipmentDelivery' => [
                            'address' => $this->getShipmentDeliveryAddress($arrShippingAddress),
                            'holdUntilDate' => null,
                            'serviceType' => $arrShippingAddress['shipMethod'],
                            'productionLocationId' => !empty($arrShippingAddress['productionLocationId']) ? $arrShippingAddress['productionLocationId'] : null,
                            'fedExAccountNumber' => $arrShippingAddress['fedExShippingAccountNumber'],
                            'deliveryInstructions' => null,
                        ],
                        'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'], false)
                    ],
                ];

                $this->checkoutSession->setServiceType($arrShippingAddress['shipMethod']);
                if (isset($arrShippingAddress['fedExAccountNumber'])) {
                    $arrRecipientsData['fedExAccountNumber'] = $arrShippingAddress['fedExAccountNumber'];
                }
                $shipmentSpecialServices = $this->deliveryHelper->getRateRequestShipmentSpecialServices();
                if (!empty($shipmentSpecialServices)) {
                    $arrRecipientsData['arrRecipients'][0]['shipmentDelivery']
                    ['specialServices'] = $shipmentSpecialServices;
                }
            }

            // Mixed Cart or Marketplace Only Cart
            if (($isMixedCart && $quote->getIsFromPickup() && is_array($arrPickupLocationdata) && $arrPickupLocationdata['locationId']) ||
                ($isMixedCart && !empty($arrShippingAddress['shipMethod'])) ||
                $this->quoteHelper->isFullMiraklQuote($quote)) {

                if (!empty($marketPlaceShippingMethodCode)) {
                    $deliveryDate = date('Y-m-d', strtotime($marketPlaceShippingDeliveryDate));
                    $quoteItem = $quote->getItemById((int)$shippingData["item_id"]);
                    $shopData = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
                    $shopArrayData = $shopData->getData();
                    $regionCode = '';
                    $regionName = $shopArrayData["additional_info"]['contact_info']['state'];
                    if (!empty($regionName)) {
                        $region = $this->collectionFactory->create()
                            ->addRegionNameFilter($regionName)
                            ->getFirstItem()
                            ->toArray();

                        if (count($region) > 0) {
                            $regionCode = $region['code'];
                        }
                    }


                    $shippingMethods = json_decode($shopArrayData['shipping_methods'], true);
                    $shippingMethodCode = $shippingData['method_code'];
                    $deliveryMethod = array_filter($shippingMethods, function ($var) use ($shippingMethodCode) {
                        return ($var['shipping_method_name'] == $shippingMethodCode);
                    });
                    $deliveryMethod = array_values($deliveryMethod);

                    $arrRecipientsData['arrRecipients'][] =
                        [
                            'contact' => null,
                            'reference' => $shippingData['reference_id'],
                            'externalDelivery' => [
                                'address' => $this->getShipmentDeliveryAddress($arrShippingAddress),
                                'originAddress' => [
                                    'streetLines' => [
                                        $shopArrayData["additional_info"]['contact_info']['street_1']
                                    ],
                                    'city' => $shopArrayData["additional_info"]['contact_info']['city'],
                                    'stateOrProvinceCode' => $regionCode,
                                    'postalCode' => $shopArrayData["additional_info"]['contact_info']['zip_code'],
                                    'countryCode' => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0]),
                                    'addressClassification' => $arrShippingAddress['addrClassification']
                                ],
                                'estimatedShipDates' => [
                                    'minimumEstimatedShipDate' => $deliveryDate,
                                    'maximumEstimatedShipDate' => $deliveryDate
                                ],
                                'skus' => [
                                    [
                                        'skuDescription' => $shippingData['method_title'],
                                        'skuRef' => $deliveryMethod[0]['shipping_method_code'],
                                        'code' => $deliveryMethod[0]['shipping_method_code'],
                                        'unitPrice' => $marketPlaceShippingMethodPrice,
                                        'price' => $marketPlaceShippingMethodPrice,
                                        'qty' => '1'
                                    ]
                                ]
                            ],
                            'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'], true)
                        ];
                    if (isset($arrShippingAddress['fedExAccountNumber'])) {
                        $arrRecipientsData['fedExAccountNumber'] = $arrShippingAddress['fedExAccountNumber'];
                    }
                }
            }
        }

        return $arrRecipientsData;
    }
    /**
     * @codeCoverageIgnore
     * Get Shipping/Pickup Data
     */
    public function getPickShipDataUpdated($quote, $itemData)
    {
        $arrRecipientsData = $shippingData = [];
        $arrRecipientsData['fedExAccountNumber'] = null;
        $arrRecipientsData['arrRecipients'] = null;

        $referenceId = $this->getShipmentId($quote);

        $arrShippingAddress = $quote->getCustomerShippingAddress() ?? null;
        $arrPickupLocationdata = $quote->getCustomerPickupLocationData() ?? null;


        $isMixedCart = false;
        if ($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote)) {
            $isMixedCart = true;
        }

        // Set variables if Mirakl quote for future use
        $isShippingDataAvailable = false;
        if ($isMixedCart || $this->quoteHelper->isFullMiraklQuote($quote)) {
            $items = $quote->getAllItems();
            $sellers=$isShippingDataAvailableArray=[];

            foreach ($items as $item) {
                if (!$item->getMiraklOfferId()) {
                    continue;
                }
                $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                $sellers[]=$item->getData('mirakl_shop_id');
                if (isset($additionalData['mirakl_shipping_data'])) {
                    $sellerId = $item->getData('mirakl_shop_id');
                    $shippingData[$sellerId] = $additionalData['mirakl_shipping_data'];
                }

            }

            foreach($sellers as $key=>$seller){
                $isShippingDataAvailableArray[] = (int)(array_key_exists($seller,$shippingData));
            }
            $isShippingDataAvailable = !(in_array(0,$isShippingDataAvailableArray));
        }

        // Pickup
        $arrRecipientsPickupData = $this->setPickup($quote,
            $arrPickupLocationdata,
            $isMixedCart,
            $isShippingDataAvailable,
            $arrShippingAddress,
            $referenceId,
            $itemData);

        // Shipping
        $arrRecipientsShipData = $this->setShippingOption($quote,
            $arrShippingAddress,
            $isMixedCart,
            $isShippingDataAvailable,
            $referenceId,
            $itemData,
            $arrPickupLocationdata,
            $shippingData);

        if(!empty($arrRecipientsPickupData) && !empty($arrRecipientsShipData)){
            $rcpData = array_merge($arrRecipientsPickupData['arrRecipients'],$arrRecipientsShipData['arrRecipients']);
            $arrRecipientsData['arrRecipients'] = $rcpData;
            $arrRecipientsData['fedExAccountNumber'] = $arrRecipientsShipData['fedExAccountNumber']??'' ;
        }
        if(empty($arrRecipientsShipData) && !empty($arrRecipientsPickupData))
        {
            $arrRecipientsData = $arrRecipientsPickupData;
        }
        if(!empty($arrRecipientsShipData) && empty($arrRecipientsPickupData))
        {
            $arrRecipientsData = $arrRecipientsShipData;
        }

        return $arrRecipientsData;
    }

    /**
     * Create and get shipment id
     * @param $quote
     * @return int|string
     */
    public function getShipmentId($quote)
    {
        if (!empty($quote->getData('fxo_shipment_id'))) {
            $shipmentId = $quote->getData('fxo_shipment_id');
        } else {
            $shipmentId = (string)(random_int(1000, 9999));
            $quote->setData('fxo_shipment_id', $shipmentId);
        }

        return $shipmentId;
    }

    /**
     * Get order notes from company
     * @return null|string
     */
    public function getOrderNotes()
    {
        return $this->companyHelper->getCompanyLevelConfig()['order_notes'] ?? null;
    }

    private function filterProductAssociation(array $products, bool $isMarketPlaceProduct): array
    {
        $associatedProducts = array_filter($products, function ($var) use ($isMarketPlaceProduct) {
            return ($var['is_marketplace'] == $isMarketPlaceProduct);
        });

        return array_values($associatedProducts);
    }


    private function getShipmentDeliveryAddress(array $shippingAddress): array
    {
        return [
            'streetLines' => $shippingAddress['street'],
            'city' => $shippingAddress['city'],
            'stateOrProvinceCode' => $shippingAddress['regionData'],
            'postalCode' => $shippingAddress['zipcode'],
            'countryCode' => 'US',
            'addressClassification' => $shippingAddress['addrClassification'],
        ];
    }

    /**
     * @param $quote
     * @param $arrPickupLocationdata
     * @param $isMixedCart
     * @param $isShippingDataAvailable
     * @param $arrShippingAddress
     * @param $referenceId
     * @param $itemData
     * @return array
     */
    private function setPickup(
        $quote,
        $arrPickupLocationdata,
        $isMixedCart,
        $isShippingDataAvailable,
        $arrShippingAddress,
        $referenceId,
        $itemData
    ){
        $arrRecipientsData = [];
        if ($quote->getIsFromPickup() && is_array($arrPickupLocationdata) && $arrPickupLocationdata['locationId']
            && $arrPickupLocationdata['locationId'] != "null") {
            if ($isMixedCart && $isShippingDataAvailable && is_array($arrShippingAddress)
                || (!$this->quoteHelper->isMiraklQuote($quote))) {

                $arrRecipientsData['arrRecipients'] = [
                    0 => [
                        'reference' => $referenceId,
                        'contact' => null,
                        'pickUpDelivery' => [
                            'location' => [
                                'id' => $arrPickupLocationdata['locationId'],
                            ],
                            'requestedPickupLocalTime' => !empty($quote->getData('requestedPickupDateTime')) ?
                                $quote->getData('requestedPickupDateTime') : null,
                        ],
                        'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'][0], false)
                    ],
                ];

                if (isset($arrPickupLocationdata['fedExAccountNumber'])) {
                    $arrRecipientsData['fedExAccountNumber'] = $arrPickupLocationdata['fedExAccountNumber'];
                }
            }
        }
        return $arrRecipientsData;
    }

    /**
     * @param $quote
     * @param $arrShippingAddress
     * @param $isMixedCart
     * @param $isShippingDataAvailable
     * @param $referenceId
     * @param $itemData
     * @param $arrPickupLocationdata
     * @return void
     */
    private function setShippingOption(
        $quote,
        $arrShippingAddress,
        $isMixedCart,
        $isShippingDataAvailable,
        $referenceId,
        $itemData,
        $arrPickupLocationdata,
        $shippingData
    ){
        $arrRecipientsData = [];
        if ($quote->getIsFromShipping() && is_array($arrShippingAddress)) {
            if (($isMixedCart && !empty($arrShippingAddress['shipMethod']) && $isShippingDataAvailable) ||
                (!$this->quoteHelper->isMiraklQuote($quote)  && !empty($arrShippingAddress['shipMethod']))) {



                // Get ShipmentId
                $arrRecipientsData['arrRecipients'] = [
                    0 => [
                        'contact' => null,
                        'reference' => $referenceId,
                        'shipmentDelivery' => [
                            'address' => $this->getShipmentDeliveryAddress($arrShippingAddress),
                            'holdUntilDate' => null,
                            'serviceType' => $arrShippingAddress['shipMethod'],
                            'productionLocationId' => !empty($arrShippingAddress['productionLocationId']) ? $arrShippingAddress['productionLocationId'] : null,
                            'fedExAccountNumber' => $arrShippingAddress['fedExShippingAccountNumber'],
                            'deliveryInstructions' => null,
                        ],
                        'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'][0], false)
                    ],
                ];

                $this->checkoutSession->setServiceType($arrShippingAddress['shipMethod']);
                if (isset($arrShippingAddress['fedExAccountNumber'])) {
                    $arrRecipientsData['fedExAccountNumber'] = $arrShippingAddress['fedExAccountNumber'];
                }
                $shipmentSpecialServices = $this->deliveryHelper->getRateRequestShipmentSpecialServices();
                if (!empty($shipmentSpecialServices)) {
                    $arrRecipientsData['arrRecipients'][0]['shipmentDelivery']
                    ['specialServices'] = $shipmentSpecialServices;
                }
            }

            // Mixed Cart or Marketplace Only Cart

            if (($isMixedCart && $quote->getIsFromPickup() && is_array($arrPickupLocationdata) && $arrPickupLocationdata['locationId']) ||
                ($isMixedCart && !empty($arrShippingAddress['shipMethod'])) ||
                $this->quoteHelper->isFullMiraklQuote($quote)) {

                if (!empty($shippingData) && $isShippingDataAvailable) {

                    foreach ($shippingData as $key => $value) {
                        $shippingDataBySeller = $shippingData[$key];
                        $deliveryDate = date('Y-m-d', strtotime($shippingDataBySeller['deliveryDate']));

                        $quoteItem = $quote->getItemById((int)$shippingDataBySeller["item_id"]);
                        if(is_bool($quoteItem)){
                            $arrRecipientsData['arrRecipients'] = null;
                        }else{
                            $shopData = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
                            $shopArrayData = $shopData->getData();
                            $regionCode = '';
                            $regionName = $shopArrayData["additional_info"]['contact_info']['state'];
                            if (!empty($regionName)) {
                                $region = $this->collectionFactory->create()
                                    ->addRegionNameFilter($regionName)
                                    ->getFirstItem()
                                    ->toArray();

                                if (count($region) > 0) {
                                    $regionCode = $region['code'];
                                }
                            }

                            if (!empty($shopArrayData['shipping_methods'])) {
                                $shippingMethods = json_decode($shopArrayData['shipping_methods'], true);
                                $shippingMethodCode = $shippingDataBySeller['method_code'];
                                $deliveryMethod = array_filter($shippingMethods, function ($var) use ($shippingMethodCode) {
                                    return ($var['shipping_method_name'] == $shippingMethodCode);
                                });
                                $deliveryMethod = array_values($deliveryMethod);
                            } else {
                                $deliveryMethod[0]['shipping_method_code'] = $shippingDataBySeller['method_code'];
                            }

                            $arrRecipientsData['arrRecipients'][] =
                                [
                                    'contact' => null,
                                    'reference' => $shippingDataBySeller['reference_id'],
                                    'externalDelivery' => [
                                        'address' => $this->getShipmentDeliveryAddress($arrShippingAddress),
                                        'originAddress' => [
                                            'streetLines' => [
                                                $shopArrayData["additional_info"]['contact_info']['street_1']
                                            ],
                                            'city' => $shopArrayData["additional_info"]['contact_info']['city'],
                                            'stateOrProvinceCode' => $regionCode,
                                            'postalCode' => $shopArrayData["additional_info"]['contact_info']['zip_code'],
                                            'countryCode' => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0]),
                                            'addressClassification' => $arrShippingAddress['addrClassification']
                                        ],
                                        'estimatedShipDates' => [
                                            'minimumEstimatedShipDate' => $deliveryDate,
                                            'maximumEstimatedShipDate' => $deliveryDate
                                        ],
                                        'skus' => [
                                            [
                                                'skuDescription' => $shippingDataBySeller['method_title'],
                                                'skuRef' => $deliveryMethod[0]['shipping_method_code'],
                                                'code' => $deliveryMethod[0]['shipping_method_code'],
                                                'unitPrice' => $shippingDataBySeller['amount'],
                                                'price' => $shippingDataBySeller['amount'],
                                                'qty' => '1'
                                            ]
                                        ]
                                    ],
                                    'productAssociations' => $this->filterProductAssociation($itemData['productAssociations'][$key], true)
                                ];
                            if (isset($arrShippingAddress['fedExAccountNumber'])) {
                                $arrRecipientsData['fedExAccountNumber'] = $arrShippingAddress['fedExAccountNumber'];
                            }
                        }
                    }

                }
            }
        }

        return $arrRecipientsData;
    }
}
