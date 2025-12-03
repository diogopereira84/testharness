<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Pay\Controller\Index;


use Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory;
use Fedex\Delivery\Api\ShippingMessageInterface;
use Fedex\FXOPricing\Api\RateAlertBuilderInterface;
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\Shipment\Helper\Data;
use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Checkout\Model\CartFactory;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Magento\Checkout\Model\Session;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\FXOPricing\Model\FXOModel;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;

class PayRateApiShipAndPick extends \Magento\Framework\App\Action\Action
{
    public const FEDEX_ACCOUNT = 'fedexAccount';
    public const STREET = 'street';
    public const REGION_ID = 'region_id';
    public const COMPANY = 'company';
    public const LOCATION_ID = 'locationId';
    public const FEDEX_ACCOUNT_NUMBER = 'fedex_account_number';
    public const OUTPUT = 'output';
    public const ALERTS = 'alerts';
    public const FREESHIPMESSAGE = 'free_shipping';

    /**
     * Constructor PayRateApiShipAndPick
     *
     * @param Context $context
     * @param LoggerInterface $loggerInterface
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param RegionFactory $regionFactory
     * @param CartFactory $cartFactory
     * @param FXORate $fxoRate
     * @param CartDataHelper $cartDataHelper
     * @param Session $checkoutSession
     * @param FXORateQuote $fxoRateQuote
     * @param FXOModel $fxoModel
     * @param ToggleConfig $toggleConfig
     * @param ShippingMessageInterface $shippingMessage
     * @param TransportInterfaceFactory $transportFactory
     * @param RateQuoteBuilderInterface $rateQuoteBuilder
     * @param RateAlertBuilderInterface $rateAlertBuilder
     */
    public function __construct(
        private readonly Context $context,
        private readonly LoggerInterface $loggerInterface,
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly RegionFactory $regionFactory,
        private readonly CartFactory $cartFactory,
        private readonly FXORate $fxoRate,
        private readonly CartDataHelper $cartDataHelper,
        private readonly Session $checkoutSession,
        private readonly FXORateQuote $fxoRateQuote,
        private readonly FXOModel $fxoModel,
        private readonly ToggleConfig $toggleConfig,
        private readonly ShippingMessageInterface $shippingMessage,
        private readonly TransportInterfaceFactory $transportFactory,
        private readonly RateQuoteBuilderInterface $rateQuoteBuilder,
        private readonly RateAlertBuilderInterface $rateAlertBuilder,
        private QuoteHelper                        $quoteHelper
    )
    {
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $regionData = null;
        $locationId = null;
        try {
            $quote = $this->cartFactory->create()->getQuote();
            $fedExAccountNumber = $this->getFedExAccountNumber($quote);
            $fedExShippingNumber = $this->getFedExShippingNumber($quote);
            $isPickupPage = $this->request->getPostValue('isPickupPage');
            $isShippingPage = $this->request->getPostValue('isShippingPage');

            $requestedPickupLocalTime = $this->request->getPostValue('requestedPickupLocalTime');
            if (!empty($requestedPickupLocalTime)) {
                $quote->setData('requestedPickupDateTime', $requestedPickupLocalTime);
            }

            $shippingAddress = $quote->getShippingAddress();
            $shipMethod = $shippingAddress->getData('shipping_method');
            if ($this->isD194518Enabled()) {
                $pickupAddress = $shippingAddress->getData('pickup_address');
                if (is_null($shipMethod) && $pickupAddress) {
                    $shipMethod = Data::SHIPPING_TYPE_PICKUP;
                    $locationId = json_decode($pickupAddress)?->Id;
                }
            }
            $streetAddress = (array) $shippingAddress->getData(self::STREET);
            $city = $shippingAddress->getData('city');
            $regionId = $shippingAddress->getData(self::REGION_ID);
            $zipcode = $shippingAddress->getData('postcode');

            $isAddressFixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix');
            $isFixOverrideEnabled = $this->toggleConfig->getToggleConfigValue('tiger_team_d219824_enable_shipping_total_fix');

            if (
                ($isFixOverrideEnabled && $isAddressFixEnabled) ||
                (!$isFixOverrideEnabled && $isAddressFixEnabled && $isShippingPage)
            ){
                $addressClassification = "BUSINESS";
                $isResidenceShipping = $shippingAddress->getData('is_residence_shipping');
                if ($isResidenceShipping) {
                    $addressClassification = "HOME";
                }
            } else {
                $addressClassification = "HOME";
                $company = $shippingAddress->getData(self::COMPANY);
                if ($company != null && $company != "") {
                    $addressClassification = "BUSINESS";
                }
            }

            if (isset($regionId)) {
                $regionData = $this->regionFactory->create()->load($regionId);
            }
            if ($shipMethod === "fedexshipping_PICKUP" || $isPickupPage) {
                $this->setFedExPickupData(
                    $locationId,
                    $shipMethod,
                    $quote,
                    $fedExAccountNumber,
                    $fedExShippingNumber
                );
            } else {
                $this->setFedexShippingData(
                    $isShippingPage,
                    $shippingAddress,
                    $shipMethod,
                    $fedExAccountNumber,
                    $fedExShippingNumber,
                    $addressClassification,
                    $quote,
                    $regionData,
                    $zipcode,
                    $streetAddress,
                    $city
                );
            }
            $quote->setIsFromAccountScreen(true);
            $arrRateResponseData = $this->getFXORateCall($quote);


            $transport = $this->transportFactory->create();
            $rateQuoteForValidation = (! empty($arrRateResponseData[self::OUTPUT]['rateQuote'])
                ? $arrRateResponseData[self::OUTPUT]['rateQuote']
                :  ! empty($arrRateResponseData['rateQuote'])) ? $arrRateResponseData['rateQuote'] : [];

            $rateQuoteForValidationAlerts = (! empty($arrRateResponseData[self::OUTPUT]['alerts'])
                ? $arrRateResponseData[self::OUTPUT]['alerts']
                :  ! empty($arrRateResponseData['alerts'])) ? $arrRateResponseData['alerts'] : [];

            if (! empty($rateQuoteForValidation)) {
                $transport
                    ->setCart($quote)
                    ->setStrategy('rateQuote')
                    ->setFXORateQuote($this->rateQuoteBuilder->build($rateQuoteForValidation));
            }

            $transport->setFXORateAlert(
                $this->rateAlertBuilder->build($rateQuoteForValidationAlerts)
            );

            $arrRateResponseData[self::FREESHIPMESSAGE] = $this->shippingMessage->getMessage($transport);
            $this->checkoutSession->setAppliedFedexAccNumber($fedExAccountNumber);

            return $this->resultJsonFactory->create()->setData($arrRateResponseData);
        } catch (\Exception $error) {
            $this->loggerInterface->critical(__METHOD__.':'.__LINE__.' '.$error->getMessage());

            return $this->resultJsonFactory->create()->setData(["errors" => "Error found no data from rateApi"]);
        }
    }

    /**
     * Get FedEx Account Number
     *
     * @param Object $quote
     * @return string
     */
    protected function getFedExAccountNumber($quote)
    {
        $removedFedexAccount = $this->request->getPostValue('removedFedexAccount');
        if ($removedFedexAccount === "true") {
            // If remove the FedexAccount
            $fedExAccountNumber = null;
            $quote->setData(self::FEDEX_ACCOUNT_NUMBER, null);
            // B-1275215: Set flag to identify customer manually removed fedex account number
            $this->checkoutSession->setRemoveFedexAccountNumber(true);
        } else {
            if (!empty($this->request->getPostValue(self::FEDEX_ACCOUNT))) {
                $fedExAccountNumber = $this->request->getPostValue(self::FEDEX_ACCOUNT);
                $quote->setData(self::FEDEX_ACCOUNT_NUMBER, $this->cartDataHelper->encryptdata($fedExAccountNumber));
            } else {
                $fedExAccountNumber = $this->cartDataHelper->decryptData(
                    $quote->getData(self::FEDEX_ACCOUNT_NUMBER)
                ) ?? "";
            }
        }

        return $fedExAccountNumber;
    }

    /**
     * Get FedEx Shipping Number
     *
     * @param Object $quote
     * @return string
     */
    protected function getFedExShippingNumber($quote)
    {
        return $quote->getData("fedex_ship_account_number") ?? $this->request->getPostValue('shippingAccount');
    }

    /**
     * Get FXO Rate Call
     *
     * @param Object $quote
     * @return array
     */
    public function getFXORateCall($quote)
    {
        $quote->setIsAjaxRequest(true);
        $arrRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
        $quote->setIsFromAccountScreen(false);
        if (!empty($arrRateResponse[self::OUTPUT])) {
            if ($this->getConditionForResultJson($arrRateResponse)
            ) {
                $this->fxoModel->handlePromoAccountWarnings($quote, $arrRateResponse[self::OUTPUT]);
            }

            return $arrRateResponse[self::OUTPUT];
        } else {
            return $arrRateResponse;
        }
    }

    /**
     * Seting Data in Quote for Shipping
     */
    public function setFedexShippingData(
        $isShippingPage,
        $shippingAddress,
        $shipMethod,
        $fedExAccountNumber,
        $fedExShippingNumber,
        $addressClassification,
        $quote,
        $regionData,
        $zipcode,
        $streetAddress,
        $city
    ) {
        // for shipping.
        if (!empty($isShippingPage) && $isShippingPage) {
            $streetAddress = (array) $this->request->getPostValue(self::STREET);
            $city = $this->request->getPostValue('city');
            $regionCode = $this->request->getPostValue(self::REGION_ID);
            $zipcode = $this->request->getPostValue('zipcode');
            $shipMethod = $this->request->getPostValue('ship_method');
        } else {
            $streetAddress = (array) $shippingAddress->getData(self::STREET);
            $city = $shippingAddress->getData('city');
            $regionCode = $shippingAddress->getData(self::REGION_ID);
            $zipcode = $shippingAddress->getData('postcode');
            $shipMethod = $shippingAddress->getData('shipping_method');
            // D-105223 - RT-ECVS-SDE- System error is showing in shipping location page
            $shipMethod = $shipMethod ? explode('_', $shipMethod, 2)[1] : null;
        }
        if (isset($regionCode)) {
            $regionData = $this->regionFactory->create()->load($regionCode);
        }
        if (isset($streetAddress[0])) {
            $streetAddress = explode(PHP_EOL, $streetAddress[0]);
        }
        $productionLocation = null;
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocationFixToggle = $this->toggleConfig->getToggleConfigValue('explorers_d188299_production_location_fix');
            if ($productionLocationFixToggle) {
                $productionLocation = $this->checkoutSession->getProductionLocationId() ?? null;
            }
            $techTitansLocationFixToggle = $this->toggleConfig->getToggleConfigValue('techtitans_205447_wrong_location_fix');
            if ($techTitansLocationFixToggle && !empty($this->request->getPostValue('selectedProductionId'))) {
                $productionLocation = $this->request->getPostValue('selectedProductionId');
            }
        }
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocation = $quote->getShippingAddress()->getProductionLocation();
        }
        if($this->toggleConfig->getToggleConfigValue('tiger_d_220707_fix') && $productionLocation == null){
            $productionLocation = $this->checkoutSession->getProductionLocationId() ?? null;
        }
        $arrCustomerShippingAddress = [
            'shipMethod' => $shipMethod,
            'zipcode' => $zipcode,
            'regionData' => isset($regionData) ? $regionData->getCode() : null,
            self::STREET => $streetAddress,
            'city' => $city,
            'fedExAccountNumber' => $fedExAccountNumber ?? null,
            'fedexDiscount' => null,
            'addrClassification' => $addressClassification,
            'fedExShippingAccountNumber' => $fedExShippingNumber == "" ? null : $fedExShippingNumber,
            'productionLocationId' => $productionLocation
        ];
        $quote->setCustomerShippingAddress($arrCustomerShippingAddress);
        $quote->setIsFromShipping(true);
    }

    /**
     * Set FedEx Pickup Data
     */
    public function setFedExPickupData(
        $locationId,
        $shipMethod,
        $quote,
        $fedExAccountNumber,
        $fedExShippingNumber = ''
    ) {
        if (empty($locationId) && $shipMethod === 'fedexshipping_PICKUP') {
            $shippingAddress = $quote->getShippingAddress();
            $locationId = $shippingAddress->getData('shipping_description');
        }
        if (!empty($this->request->getPostValue(self::LOCATION_ID))) {
            $locationId = $this->request->getPostValue(self::LOCATION_ID);
        }

        $arrRequestPickupData = [
            self::LOCATION_ID => $locationId,
            'fedExAccountNumber' => $fedExAccountNumber,
        ];
        $quote->setCustomerPickupLocationData($arrRequestPickupData);
        $quote->setIsFromPickup(true);

        if ($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote)) {
            $items = $quote->getAllItems();
            foreach ($items as $item) {
                if ($item->getMiraklOfferId()) {
                    $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                    if (isset($additionalData['mirakl_shipping_data']['address'])) {
                        $shippingData = $additionalData['mirakl_shipping_data']['address'];
                        $addressClassification = "HOME";
                        $company = $shippingData['company'];
                        if (!empty($company)) {
                            $addressClassification = "BUSINESS";
                        }

                        $arrCustomerShippingAddress = [
                            'shipMethod' => '',
                            'zipcode' => $shippingData['postcode'],
                            'regionData' => $shippingData['region'],
                            self::STREET => $shippingData['street'],
                            'city' => $shippingData['city'],
                            'fedExAccountNumber' => $fedExAccountNumber ?? null,
                            'fedexDiscount' => null,
                            'addrClassification' => $addressClassification,
                            'fedExShippingAccountNumber' => $fedExShippingNumber == "" ? null : $fedExShippingNumber,
                            'productionLocationId' => null
                        ];
                        $quote->setCustomerShippingAddress($arrCustomerShippingAddress);
                        $quote->setIsFromShipping(true);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Condition for sending data on result json
     */
    public function getConditionForResultJson($arrRateResponse)
    {
        return (
            !empty($arrRateResponse[self::OUTPUT][self::ALERTS]) &&
            !empty($arrRateResponse[self::OUTPUT][self::ALERTS][0]['code'])
        );
    }

    /**
     * @return bool|int
     */
    private function isD194518Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue('tiger_d194518');
    }
}
