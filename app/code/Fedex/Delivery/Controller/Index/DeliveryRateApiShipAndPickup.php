<?php
declare(strict_types=1);
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Delivery\Controller\Index;

use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\Delivery\Api\ShippingMessageInterface;
use Fedex\Delivery\Model\ShippingMessage\TransportInterfaceFactory;
use Fedex\FXOPricing\Api\RateAlertBuilderInterface;
use Fedex\FXOPricing\Api\RateBuilderInterface;
use Fedex\FXOPricing\Api\RateQuoteBuilderInterface;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\FXOPricing\Model\FXOModel;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\InBranch\Model\InBranchValidation;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session As customerSession;

class DeliveryRateApiShipAndPickup extends \Magento\Framework\App\Action\Action
{
    public const LOCATION_ID = 'locationId';
    public const OUTPUT = 'output';
    public const RATE = 'rate';
    public const RATE_QUOTE = 'rateQuote';
    public const ALERTS = 'alerts';
    public const ERRORS = 'errors';
    public const SHIP_METHOD = 'ship_method';
    public const REGION_ID = 'region_id';
    public const ZIPCODE = 'zipcode';
    public const STREET = 'street';
    public const SHOWFREESHIPMSG = 'show_free_shipping_message';
    public const FREESHIPMSG = 'free_shipping_message';
    public const FREESHIPMESSAGE = 'free_shipping';
    public const TRANSACTIONID = 'transactionId';

    /**
     * RateApi Constructor
     *
     * @param Context $context
     * @param Data $helper
     * @param CartFactory $cartFactory
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param RegionFactory $regionFactory
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CompanyRepositoryInterface $companyRepository
     * @param FXORate $fxoPricingHelper
     * @param CartDataHelper $cartDataHelper
     * @param SdeHelper $sdeHelper
     * @param QuoteDataHelper $quoteDataHelper
     * @param FXORateQuote $fxoRateQuote
     * @param ToggleConfig $toggleConfig
     * @param FXOModel $fxoModel
     * @param DeliveryHelper $deliveryHelper
     * @param QuoteOptions $quoteOptions
     * @param ShippingMessageInterface $shippingMessage
     * @param TransportInterfaceFactory $transportFactory
     * @param RateBuilderInterface $rateBuilder
     * @param RateQuoteBuilderInterface $rateQuoteBuilder
     * @param RateAlertBuilderInterface $rateAlertBuilder
     * @param QuoteHelper $quoteHelper
     * @param InBranchValidation $inBranchValidation
     */
    public function __construct(
        protected Context                    $context,
        private DeliveryHelper               $helper,
        protected CartFactory                $cartFactory,
        private LoggerInterface              $logger,
        protected RequestInterface           $request,
        protected RegionFactory              $regionFactory,
        protected JsonFactory                $resultJsonFactory,
        private Session                      $checkoutSession,
        private customerSession              $customerSession,
        protected CompanyRepositoryInterface $companyRepository,
        protected FXORate                    $fxoPricingHelper,
        protected CartDataHelper             $cartDataHelper,
        protected SdeHelper                  $sdeHelper,
        protected QuoteDataHelper            $quoteDataHelper,
        protected FXORateQuote               $fxoRateQuote,
        protected ToggleConfig               $toggleConfig,
        protected FXOModel                   $fxoModel,
        protected DeliveryHelper             $deliveryHelper,
        protected QuoteOptions                $quoteOptions,
        private readonly ShippingMessageInterface  $shippingMessage,
        private readonly TransportInterfaceFactory $transportFactory,
        private readonly RateBuilderInterface      $rateBuilder,
        private readonly RateQuoteBuilderInterface $rateQuoteBuilder,
        private readonly RateAlertBuilderInterface $rateAlertBuilder,
        private QuoteHelper                        $quoteHelper,
        private InBranchValidation                 $inBranchValidation
    )
    {
        parent::__construct($context);
    }

    /**
     * Execute Constroller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $fedExAccountNumber = null;
        $isPickupPage = null;
        $returnData = [];
        $resultJson = $this->resultJsonFactory->create();

        try {
            $quote = $this->cartFactory->create()->getQuote();
            $requestData = $this->getRequest()->getContent();
            $requestDataDecoded = json_decode((string)$requestData, true);
            if (is_array($requestDataDecoded)) {
                $isPickupPage = $requestDataDecoded['isPickupPage'];
            }
            $requestPostData = $this->request->getPost();

            $isRateApi = isset($requestPostData['isRateApi']) ? filter_var($requestPostData['isRateApi'], FILTER_VALIDATE_BOOLEAN) : false;

            // We are checking both because pickup uses locationId and shipping uses location_id
            $locationId = !empty($this->request->getPostValue(self::LOCATION_ID)) ?
                $this->request->getPostValue(self::LOCATION_ID) : $this->request->getPostValue('location_id');

            $tigerD193772Fix = (bool) $this->toggleConfig->getToggleConfigValue('tiger_d193772_fix');
            if ($locationId && $tigerD193772Fix) {
                $isPickupPage = true;
            }

            // Set Pickup Flag
            $this->setPickupPageLocation($quote, $requestDataDecoded, $isRateApi);

            //B-1275215: Get fedex account number if already saved in quote
            $fedExAccountNumber = $this->getFedexAccountNumber($quote);

            // Get Coupon Flag for appling coupon code on checkout page
            $couponAppliedFromSidebar = $this->getCouponCodeFromSidebar($requestDataDecoded);

            $quote->setData('fedex_ship_account_number', $requestPostData['fedEx_account_number']);

            if (!empty($requestDataDecoded['requestedPickupLocalTime']) && !empty($requestDataDecoded['pickupPageLocation'])) {
                $quote->setData('requestedPickupDateTime', $requestDataDecoded['requestedPickupLocalTime']);
            }

            $this->setShippingData($quote, $requestPostData, $requestDataDecoded, $fedExAccountNumber, $isRateApi, $isPickupPage, $tigerD193772Fix);

            //Save 3P shipping option in QuoteItem->AdditionalData
           $this->save3pOptionsInQuoteItem($requestPostData,$quote);

            $rateApiKey = 'rate';
            $transport = $this->transportFactory->create();
            if (!$this->fxoPricingHelper->isEproCustomer() && !$isRateApi) {
                $quote->setIsAjaxRequest(true);
                $arrRateResponse = $this->fxoRateQuote->getFXORateQuote($quote);
                $rateApiKey = 'rateQuote';
                if (isset($arrRateResponse[self::OUTPUT][$rateApiKey])) {
                    $transport
                        ->setCart($quote)
                        ->setStrategy($rateApiKey)
                        ->setFXORateQuote($this->rateQuoteBuilder->build($arrRateResponse[self::OUTPUT][$rateApiKey]));
                }
            } else {
                $arrRateResponse = $this->fxoPricingHelper->getFXORate($quote);

                if (isset($arrRateResponse[self::OUTPUT][$rateApiKey])) {
                    $transport
                        ->setCart($quote)
                        ->setStrategy($rateApiKey)
                        ->setFXORate($this->rateBuilder->build($arrRateResponse[self::OUTPUT][$rateApiKey]));
                }
            }

            $transport->setFXORateAlert(
                $this->rateAlertBuilder->build($arrRateResponse[self::OUTPUT]["alerts"] ?? [])
            );

            $arrRateResponse[self::OUTPUT][self::FREESHIPMESSAGE] = $this->shippingMessage->getMessage($transport);

            if (!empty($arrRateResponse[self::OUTPUT][$rateApiKey])) {
                // Handling Alert Cases

                return $this->handlingAlertCase(
                    $arrRateResponse,
                    $quote,
                    $couponAppliedFromSidebar,
                    $resultJson
                );
            }
            $returnData = $this->setErrorReturnData($arrRateResponse);
        } catch (\Exception $error) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' No data being returned from rate api: ' . $error->getMessage());
            $returnError = [];
            $returnError[self::ERRORS]['transactionId'] = '';
            $returnError[self::ERRORS][self::ERRORS][0]['code'] = 'SHIPMENTDELIVERY.API.FAILURE';
            $returnError[self::ERRORS][self::ERRORS][0]['message'] = 'Error found no data from rateApi';
            $returnData = $returnError;
        }

        return $resultJson->setData($returnData);
    }

    /**
     * setPickupData
     */
    public function setPickupData($quote, $locationId, $requestDataDecoded, $fedExAccountNumber): void
    {
        if (!empty($requestDataDecoded)) {
            $locationId = $requestDataDecoded[self::LOCATION_ID];
            $this->setCouponData($quote, $requestDataDecoded);
        }
        $arrRequestPickupData = [
            self::LOCATION_ID => $locationId,
            'fedExAccountNumber' => $fedExAccountNumber,
        ];
        $quote->setCustomerPickupLocationData($arrRequestPickupData);
        $quote->setIsFromPickup(true);
    }

    /**
     * setShippingData
     */
    public function setShippingData($quote, $requestPostData, $requestDataDecoded, $fedExAccountNumber, $isRateApi, $isPickupPage = false, $tigerD193772Fix = false): void
    {
        $this->setContactInformation($quote, $requestPostData, $isRateApi);

        $fedExShippingAccountNumber = null;
        if (!empty($requestPostData->{'fedEx_account_number'})) {
            $fedExShippingAccountNumber = $requestPostData->{'fedEx_account_number'};
        } elseif (!empty($requestDataDecoded) && isset($requestDataDecoded['shipfedexAccountNumber'])) {
            $fedExShippingAccountNumber = $requestDataDecoded['shipfedexAccountNumber'];
        }

        $shipMethod = null;
        if (!empty($requestPostData->{self::SHIP_METHOD})) {
            $shipMethod = $requestPostData->{self::SHIP_METHOD};
        }
        $zipcode = null;
        if (!empty($requestPostData->{self::ZIPCODE})) {
            $zipcode = $requestPostData->{self::ZIPCODE};
        }
        $regionId = null;
        if (!empty($requestPostData->{self::REGION_ID})) {
            $regionId = $requestPostData->{self::REGION_ID};
        }
        $street = null;
        if (!empty($requestPostData->{self::STREET})) {
            $street = $requestPostData->{self::STREET};
        }
        $city = null;
        if (!empty($requestPostData->{'city'})) {
            $city = $requestPostData->{'city'};
        }
        $addressClassification = "HOME";
        if (!empty($requestPostData->{'company'}) && $requestPostData->{'company'} != null) {
            $addressClassification = "BUSINESS";
        }
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocationId = $this->getProductionLocationId();

            // D-188299::Production Location Fix
            $productionLocationFixToggle = $this->toggleConfig->getToggleConfigValue('explorers_d188299_production_location_fix');
            if ($productionLocationFixToggle && empty($productionLocationId) && !empty($requestPostData->{'location_id'})) {
                if (!empty($requestPostData->{'location_id'})) {
                    $productionLocationId = $requestPostData->{'location_id'};
                    $this->checkoutSession->setProductionLocationId($productionLocationId);
                    if ($tigerD193772Fix && $this->helper->isCommercialCustomer()) {
                        $isPickupPage = false;
                    }
                } else {
                    $this->checkoutSession->unsProductionLocationId();
                }
            }
        }
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocationId = null;
            if (!empty($requestPostData->{'ship_method_data'})) {
                $shipMethodData = json_decode($requestPostData->{'ship_method_data'}, true);
                $extensionAttributes = $shipMethodData['extension_attributes'] ?? [];
                $productionLocation = $extensionAttributes['production_location'] ?? null;

                 if ($this->toggleConfig->getToggleConfigValue('tiger_d_220707_fix')) {
                    if (isset($productionLocation) && $productionLocation !== '') {
                        $productionLocationId = $productionLocation;
                        $this->checkoutSession->setProductionLocationId($productionLocationId);
                    }else{
                        $productionLocationId = $this->checkoutSession->getProductionLocationId();

                    }
                }else{
                     if (!empty($productionLocation)) {
                         $productionLocationId = $productionLocation;
                     }
                 }

                $isPickupPage = false;
            }
        }

        if (!$isPickupPage && $this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
            $addressClassification = "BUSINESS";
            if ($this->toggleConfig->getToggleConfigValue('tiger_d213977')) {
                if (!empty($requestPostData->{'is_residence_shipping'}) && $requestPostData->{'is_residence_shipping'} != null
                    && $requestPostData->{'is_residence_shipping'} != 'false') {
                    $addressClassification = "HOME";
                }
            } else {
                if (!empty($requestPostData->{'is_residence_shipping'}) && $requestPostData->{'is_residence_shipping'} != null) {
                    $addressClassification = "HOME";
                }
            }
        } else {
            if (!$isPickupPage && $this->toggleConfig->getToggleConfigValue('explorers_d196997_fix') && $this->cartDataHelper->getAddressClassification()) {
               $addressClassification = $this->cartDataHelper->getAddressClassification();
            }
        }

        if (!empty($requestDataDecoded) && !$isPickupPage) {
            $shipMethod = $requestDataDecoded[self::SHIP_METHOD];
            $zipcode = $requestDataDecoded[self::ZIPCODE];
            $regionId = $requestDataDecoded[self::REGION_ID];
            $street = $requestDataDecoded[self::STREET];
            $city = $requestDataDecoded['city'];

            $this->setCouponData($quote, $requestDataDecoded);
        }

        $regionData = $this->getRegionData($regionId);

        if ($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote)) {

            /**
             * Set 1P shipping method in session if mixed cart and 3P method selected.
             * This is needed for Rate Quote to function.
             */
            if (!$isPickupPage) {
                if (!$requestPostData->count()) {
                    $ship_method_data = $requestDataDecoded['ship_method_data'];
                    if (isset($ship_method_data['item_id']) && isset($requestDataDecoded['first_party_method_code'])) {
                        $shipMethod = $requestDataDecoded['first_party_method_code'];
                    }
                } else {
                    $ship_method_data = json_decode($requestPostData->ship_method_data ?? '{}', true);
                    if (isset($ship_method_data['item_id']) && isset($requestPostData->{'first_party_method_code'})) {
                        $shipMethod = $requestPostData->{'first_party_method_code'};
                    }
                }
            }

            /**
             * Set Pick Up location ID in session if mixed cart and Store Pick up selected.
             * This is needed for Rate Quote to function.
             */
            if ($isPickupPage) {
                $locationId = isset($requestPostData->location_id) && !empty($requestPostData->location_id)
                    ? $requestPostData->location_id : (!empty($this->request->getPostValue('locationId'))
                    ? $this->request->getPostValue('locationId') : null);

                if (!isset($locationId) && empty($locationId)) {
                    $locationId = isset($requestDataDecoded[self::LOCATION_ID]) ? $requestDataDecoded[self::LOCATION_ID] : null;
                }

                if ((isset($locationId) && !empty($locationId)) || $isPickupPage) {
                    $shipMethod = null;
                    $this->setPickupData($quote, $locationId, $requestDataDecoded, $fedExAccountNumber);
                }
            }
        }

        if (!$this->quoteHelper->isMiraklQuote($quote) && $isPickupPage && $tigerD193772Fix) {
            $locationId = !empty($this->request->getPostValue(self::LOCATION_ID)) ?
            $this->request->getPostValue(self::LOCATION_ID) : $this->request->getPostValue('location_id');
            $this->setPickupData($quote, $locationId, $requestDataDecoded, $fedExAccountNumber);
        }

        $arrCustomerShippingAddress = [
            'shipMethod' => $shipMethod,
            self::ZIPCODE => $zipcode,
            'regionData' => isset($regionData) ? $regionData->getCode() : null,
            self::STREET => $street,
            'city' => $city,
            'fedExAccountNumber' => $fedExAccountNumber,
            'addrClassification' => $addressClassification,
            'fedExShippingAccountNumber' => $fedExShippingAccountNumber,
            'productionLocationId' => $productionLocationId,
        ];

        if (!empty($arrCustomerShippingAddress['zipcode'])) {
            $quote->setCustomerShippingAddress($arrCustomerShippingAddress);
            $quote->setIsFromShipping(true);
        }
    }

    /**
     * getRegionData
     */
    public function getRegionData($regionId)
    {
        $regionData = null;
        if (isset($regionId)) {
            $regionData = $this->regionFactory->create()->load($regionId);
        }

        return $regionData;
    }

    /**
     * setCouponData
     */
    public function setCouponData($quote, $requestDataDecoded): void
    {
        $couponCode = null;
        if (!empty($requestDataDecoded['coupon_code'])) {
            $couponCode = $requestDataDecoded['coupon_code'];
        }
        $removeCoupon = null;
        if (!empty($requestDataDecoded['remove_coupon'])) {
            $removeCoupon = $requestDataDecoded['remove_coupon'];
        }
        // Check coupon Code for POST Request
        if ($couponCode || $removeCoupon) {
            $couponCode = $removeCoupon ? null : $couponCode;
            $quote->setCouponCode($couponCode);
        }
    }

    /**
     * getProductionLocationId
     */
    public function getProductionLocationId()
    {
        $productionLocationId = null;

        if ($this->helper->isCommercialCustomer()) {
            $companyId = $this->customerSession->getCustomerCompany();
            $customerRepo = $this->companyRepository->get((int)$companyId);

            if ($customerRepo->getAllowProductionLocation() == 1
                && $customerRepo->getProductionLocationOption() == 'recommended_location_all_locations'
                && $this->checkoutSession->getProductionLocationId() != null
                && $this->checkoutSession->getProductionLocationId() != '') {
                $productionLocationId = $this->checkoutSession->getProductionLocationId();
            }
            //Inbranch Implementation
            $isEproStore = $this->inBranchValidation->isInBranchUser();
            $productionLocationId = $this->inBranchValidation->getAllowedInBranchLocation();
            if ($isEproStore) {
                return $productionLocationId;
            }
            //Inbranch Implementation
        }

        return $productionLocationId;
    }

    /**
     * Set Return Data in Error Case
     */
    public function setErrorReturnData($arrRateResponse)
    {
        return [self::ERRORS => $arrRateResponse];
    }

    /**
     * Condition for sending data on result json
     */
    public function getConditionForResultJson($arrRateResponse)
    {
        return (
            !empty($arrRateResponse[self::OUTPUT][self::ALERTS]) &&
            !empty($arrRateResponse[self::OUTPUT][self::ALERTS][0]['code']) &&
            !$this->sdeHelper->getIsSdeStore()
        );
    }

    /**
     * Returning the Result Json Data
     */
    public function returnJsonData($resultJson, $arrRateResponse)
    {
        return $resultJson->setData($arrRateResponse[self::OUTPUT]);
    }

    /**
     * Handling the Quote Data when coupon not applied from sidebar
     */
    public function handlingDataWhenCouponNotAppliedFromSidebar(
        $arrRateResponse,
        $couponAppliedFromSidebar,
        $quote
    )
    {
        if ($this->getConditionForResultJson($arrRateResponse) &&
            !$couponAppliedFromSidebar
        ) {
            $alertMessage = $this->fxoPricingHelper
                ->removePromoCode($arrRateResponse[self::OUTPUT], $quote);
            if ($alertMessage) {
                $quote->setCouponCode();
                $quote->save();
            }
        }
    }

    /**
     * Get FedEx Account from Quote
     */
    public function getFedexAccountNumber($quote)
    {
        if ($quote->getData('fedex_account_number')) {
            return $this->cartDataHelper->decryptData($quote->getData('fedex_account_number'));
        }
    }

    /**
     * Get Coupon Code from Sidebar
     */
    public function getCouponCodeFromSidebar($requestDataDecoded)
    {
        if (!empty($requestDataDecoded['couponAppliedFromSidebar'])) {
            return $requestDataDecoded['couponAppliedFromSidebar'];
        }
        return false;
    }

    /**
     * Handling the Alert Case
     */
    public function handlingAlertCase(
        $arrRateResponse,
        $quote,
        $couponAppliedFromSidebar,
        $resultJson
    )
    {
        if ($this->getConditionForResultJson($arrRateResponse) &&
            $couponAppliedFromSidebar
        ) {
            $this->fxoModel->handlePromoAccountWarnings($quote, $arrRateResponse[self::OUTPUT]);
            return $this->returnJsonData($resultJson, $arrRateResponse);
        }
        // If Coupon Code Not applied from Sidebar
        $this->handlingDataWhenCouponNotAppliedFromSidebar(
            $arrRateResponse,
            $couponAppliedFromSidebar,
            $quote
        );

        if (isset($arrRateResponse[self::TRANSACTIONID])) {
            $arrRateResponse[self::OUTPUT][self::TRANSACTIONID] = $arrRateResponse[self::TRANSACTIONID];
        }

        // Sending Output in resultJson
        return $this->returnJsonData($resultJson, $arrRateResponse);
    }

    /**
     * Set contact information in Quote to pass in RateQuote with SAVE action
     */
    public function setContactInformation($quote, $requestPostData, $isRateApi)
    {
        if (!$isRateApi) {
            if (!empty($requestPostData->{'firstname'})) {
                $quote->setCustomerFirstname($requestPostData->{'firstname'});
            }
            if (!empty($requestPostData->{'lastname'})) {
                $quote->setCustomerLastname($requestPostData->{'lastname'});
            }
            if (!empty($requestPostData->{'email'})) {
                $quote->setCustomerEmail($requestPostData->{'email'});
            }
            if (!empty($requestPostData->{'telephone'})) {
                $quote->setCustomerTelephone($requestPostData->{'telephone'});
            }
        }
    }

    /**
     * Set setPickupPageLocation Flag to pass null
     * with Previous Quote id in rateQuote
     * @param Object $quote
     * @param Object $requestDataDecoded
     */
    public function setPickupPageLocation($quote, $requestDataDecoded, $isRateApi)
    {
        if (!$isRateApi) {
            if (!empty($this->request->getPostValue('pickupPageLocation'))) {
                $quote->setData('pickupPageLocation', $this->request->getPostValue('pickupPageLocation'));
            } elseif (!empty($requestDataDecoded['pickupPageLocation'])) {
                $quote->setData('pickupPageLocation', $requestDataDecoded['pickupPageLocation']);
            }
        }
    }

    /**
     * @param $requestPostData
     * @param $quote
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function save3pOptionsInQuoteItem($requestPostData, $quote)
    {
        $this->quoteOptions->setMktShipMethodDataItemOptionsUpdated($requestPostData->ship_method_data, $quote);
    }
}
