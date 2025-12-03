<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Delivery\Controller\Quote;

use Magento\Framework\App\Action\Context;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote\Address;
use Magento\Checkout\Model\CartFactory;
use Magento\Directory\Model\Region;
use Fedex\Purchaseorder\Model\QuoteCreation;
use Fedex\Delivery\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Shipto\Helper\Data as ShipToHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Mars\Helper\PublishToQueue;
use Fedex\Mars\Model\Config as MarsConfig;

/**
 * CreatePost Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Createpost extends \Magento\Framework\App\Action\Action
{
    public const SHIPPING_INFORMATION = 'shippingInformation';
    public const ADDRESS_INFORMATION = 'addressInformation';
    public const FIRST_NAME = 'firstName';
    public const LAST_NAME = 'lastName';
    public const EMAIL = 'email';
    public const NUMBER = 'number';
    public const EXT_NO = 'ext_no';
    public const AFIRST_NAME = 'afirstName';
    public const ALAST_NAME = 'alastName';
    public const A_EMAIL = 'aEmail';
    public const A_NUMBER = 'aNumber';
    public const A_EXT_NO = 'aExtNo';
    public const REGION = 'region';
    public const XML_PATH_D203990_TOGGLE = 'tiger_d203990';
    public const XML_PATH_D237618_TOGGLE = 'mazegeeks_d237618';

    /**
     * @param Context $context
     * @param Address $address
     * @param CartFactory $cartFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param Region $region
     * @param QuoteCreation $quoteCreation
     * @param CartRepositoryInterface $quoteRepository
     * @param JsonFactory $resultJsonFactory
     * @param ToggleConfig $toggleConfig
     * @param ShipToHelper $shipToHelper
     * @param SelfReg $selfregHelper
     * @param QuoteDataHelper $quoteDataHelper
     * @param UploadToQuoteViewModel $uploadToQuoteViewModel
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param PublishToQueue $publish
     * @param MarsConfig $marsConfig
     */
    public function __construct(
        protected Context $context,
        protected Address $address,
        protected CartFactory $cartFactory,
        protected CheckoutSession $checkoutSession,
        protected CustomerSession $customerSession,
        protected Data $helper,
        protected LoggerInterface $logger,
        protected Region $region,
        protected QuoteCreation $quoteCreation,
        protected CartRepositoryInterface $quoteRepository,
        private JsonFactory $resultJsonFactory,
        protected ToggleConfig $toggleConfig,
        protected ShipToHelper $shipToHelper,
        protected SelfReg $selfregHelper,
        protected QuoteDataHelper $quoteDataHelper,
        protected UploadToQuoteViewModel $uploadToQuoteViewModel,
        protected QuoteEmailHelper $quoteEmailHelper,
        private PublishToQueue $publish,
        private MarsConfig $marsConfig
    ) {
        parent::__construct($context);
    }

    /**
     * Save or create Quote via the Pickup address
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Exception if Error in creating the quote
     */
    public function execute()
    {
        $quote = $this->cartFactory->create()->getQuote();
        $resultJson = $this->resultJsonFactory->create();
        $requestPostData = $this->getRequest()->getPost('data');
        $isSelfRegCustomer = $this->selfregHelper->isSelfRegCustomer();
        $isEproCustomer = $this->helper->isEproCustomer();
        $isAutoCartTransmissiontoERPEnabled = $this->helper->isAutoCartTransmissiontoERPToggleEnabled();
        $isEproUploadToFeatureToggleEnabled = $this->toggleConfig->getToggleConfigValue(
            'explorers_epro_upload_to_quote'
        );
        $isCheckoutQuotePriceDashable = $this->uploadToQuoteViewModel->checkoutQuotePriceisDashable();

        if ($isEproUploadToFeatureToggleEnabled && $isEproCustomer && !$isSelfRegCustomer) {
            $quote->setData('is_epro_quote', 1);
        }

        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D203990_TOGGLE)) {
            $quote->setData('ext_shipping_info', null);
        }

        if (empty($quote->getCreatedByLocationId())) {
            $uploadrequestPostData = $requestPostData;
            $uploadrequestPostData = json_decode($uploadrequestPostData);
            $regionCode = $uploadrequestPostData->addressInformation->pickup_location_state ?? '';
            $quote->setData('created_by_location_id', $regionCode);
        }

        if ($isCheckoutQuotePriceDashable) {
            $requestPostData = json_decode($requestPostData);
            $submitReturnData = $this->submitUploadToQuote(
                $quote,
                $requestPostData,
                $isCheckoutQuotePriceDashable
            );

            return $resultJson->setData($submitReturnData);
        }
        
        $invalidRequest = $this->validateRequestData($quote, $requestPostData, $isSelfRegCustomer);
        if ($invalidRequest) {
            return $resultJson->setData($invalidRequest);
        }

        $requestPostData = json_decode($requestPostData);
        $this->setQuoteContactInformation($quote, $requestPostData);

        // Save pickup time
        $this->savePickupTime($quote, $requestPostData);

        $shippingData = [];
        if (!empty($requestPostData)) {
            $shippingData = $this->getShippingInformationData($requestPostData);
        }
        
        $quoteId = $quote->getId();
        $negotiableQuoteCreateData = $this->quoteDataHelper->getNegotiableQuoteCreateData(
            $quoteId,
            $shippingData
        );
        $negotiableQuoteCreateDataEncoded = json_encode($negotiableQuoteCreateData);
        $requestData = json_decode($negotiableQuoteCreateDataEncoded, true);
        $this->quoteDataHelper->setShippingInformation($quote, $requestData);
        $this->setShippingDataInCheckoutSession($requestData);
        $this->updateQuotePrices($quote, $requestPostData);
        $notification = null;
        $isNegotiatedQuoteExist = true;

        if ($isEproCustomer && !$isSelfRegCustomer) {
            // Create Negotiable Quote.
            if ($isEproUploadToFeatureToggleEnabled) {
                if (!$this->quoteDataHelper->checkNegotiableQuoteExistingForQuote($quoteId)) {
                    $this->quoteDataHelper->createNegotiableQuote(
                        $quote,
                        $this->quoteRepository,
                        $requestData
                    );
                    $isNegotiatedQuoteExist = false;
                }
            } else {
                $this->quoteDataHelper->createNegotiableQuote($quote, $this->quoteRepository, $requestData);
            }

            if ($isEproUploadToFeatureToggleEnabled && $isAutoCartTransmissiontoERPEnabled) {
                $notification = $this->helper->sendNotification('edit', 'final');
            } else {
                $notification = $this->helper->sendNotification();
            }
        } else {
                
            $quote->save();
            // Save shipping address
            if ($this->helper->isCommercialCustomer() && !$isSelfRegCustomer) {
                $this->quoteCreation->saveShippingAddress($requestData[self::SHIPPING_INFORMATION], $quoteId);
            }
        }
        $this->saveQuoteAddress($quoteId, $requestPostData);

        $returnData = [];
        if ($this->helper->isCommercialCustomer() && !$isSelfRegCustomer) {
            $redirectionUrl = $this->helper->getRedirectUrl();
            $this->clearShippingDataAndCheckoutSession($quote);

            if ($isEproUploadToFeatureToggleEnabled && $isNegotiatedQuoteExist && $isEproCustomer && !$isSelfRegCustomer) {
                $eproNegotiableDeclinedQuote = $quote->getExtensionAttributes()->getNegotiableQuote()->getStatus();
                if ($eproNegotiableDeclinedQuote == NegotiableQuoteInterface::STATUS_DECLINED) {
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__.
                         ':Epro negotiable quote was declined for this quoteId: '.$quoteId
                    );
                    $quoteStatus = NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN;
                    $this->uploadToQuoteViewModel->updateEproQuoteStatusByKey($quoteId, $quoteStatus);
                    $this->logger->info(
                        __METHOD__ . ':' . __LINE__.
                         ':Finally Epro upload to quote converted into normal quote for this quoteId: '.$quoteId
                    );
                }
                $quote->setData('sent_to_erp', 1);
                $this->uploadToQuoteViewModel->updateEproNegotiableQuote($quote);
                $quote->getExtensionAttributes()->getNegotiableQuote()->save();
                $quote->setIsActive(0);
            }
            $quote->save();
            $this->checkoutSession->clearQuote();
            $this->customerSession->logout()->setLastCustomerId(
                $this->customerSession->getCustomer()->getId()
            );

            $returnData = ['notification' => base64_encode($notification), 'url' => $redirectionUrl];
        }

        return $resultJson->setData($returnData);
    }

    /**
     * Update Quote Price
     *
     * @param object $quote
     * @param object $requestPostData
     * @param boolean $isCheckoutQuotePriceDashable
     *
     * @return array
     */
    public function submitUploadToQuote($quote, $requestPostData, $isCheckoutQuotePriceDashable)
    {
        try {
            $shippingData = [];
            $quoteId = $quote->getId();
            $this->setQuoteContactInformation($quote, $requestPostData);
            $this->saveQuoteAddress($quoteId, $requestPostData);
            $isAutoCartTransmissiontoERPEnabled = $this->helper->isAutoCartTransmissiontoERPToggleEnabled();
            $shippingData = $this->getShippingInformationData($requestPostData);

            $negotiableQuoteCreateData = $this->quoteDataHelper->getNegotiableQuoteCreateData($quoteId, $shippingData);
            $negotiableQuoteCreateDataEncoded = json_encode($negotiableQuoteCreateData);
            $requestData = json_decode($negotiableQuoteCreateDataEncoded, true);

            $isQuoteNegotiated = false;
            if (!empty($requestData)) {
                $uploadToQuote = [
                    "isUploadToQuote" => true,
                    "isNegotiableQuoteSi" => $isCheckoutQuotePriceDashable
                ];

                $this->quoteDataHelper->setShippingInformation($quote, $requestData);
                if ($this->uploadToQuoteViewModel->isQuoteNegotiated($quote->getId())) {
                    $isQuoteNegotiated = true;
                }
                $this->quoteDataHelper->createNegotiableQuote(
                    $quote,
                    $this->quoteRepository,
                    $requestData,
                    $uploadToQuote,
                    $isQuoteNegotiated
                );
                if (!$isQuoteNegotiated) {
                    $quoteData=[
                        'quote_id' => $quoteId,
                        'status' => 'confirmed',
                    ];
                    $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                }
            }

            $redirectionUrl = $this->uploadToQuoteViewModel->getUploadToQuoteSuccessUrl();
            $this->clearShippingDataAndCheckoutSession($quote);
            $this->checkoutSession->clearQuote();
            $this->customerSession->setUploadToQuoteId($quoteId);
            $quoteStatus = NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN;
            if ($isQuoteNegotiated) {
                $quoteStatus = NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER;
                $this->uploadToQuoteViewModel->updateLogHistory($quoteId, $quoteStatus);
                $quoteData=[
                    'quote_id' => $quoteId,
                    'status' => 'change_request',
                ];
                $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
            }
            $this->uploadToQuoteViewModel->updateQuoteStatusByKey($quoteId, $quoteStatus);

            if ($this->marsConfig->isEnabled()) {
                $this->publish->publish((int)$quoteId, 'negotiableQuote');
            }

            if ($isAutoCartTransmissiontoERPEnabled && $this->helper->isEproCustomer()
             && !$this->selfregHelper->isSelfRegCustomer()) {
                $notification = $this->helper->sendNotification('create', 'pending');
                $returnData = [
                    'notification' => base64_encode($notification),
                    'url' => $redirectionUrl,
                    'quoteId' => $quoteId
                ];
            } else {
                $returnData = [
                    'url' => $redirectionUrl,
                    'quoteId' => $quoteId
                ];
            }
        } catch (\Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ':Error in create upload to quote: ',
                ['exception' => $e->getMessage()]
            );

            $returnData = ['url' => null, 'quoteId' => null];
        }

        return $returnData;
    }

    /**
     * Update Quote Price
     */
    public function updateQuotePrices($quote, $requestPostData)
    {
        $rateapiResponseArray = [];
        $rateapiResponse = isset($requestPostData->rateapi_response) ? $requestPostData->rateapi_response : null;
        if ($rateapiResponse) {
            $rateapiResponseArray = json_decode($rateapiResponse, true);
            if (count($rateapiResponseArray)) {
                $quoteItems = $quote->getAllItems();
                $couponCode = $quote->getData("coupon_code");
                $this->helper->updateCartItemPrice($quoteItems, $rateapiResponseArray);
                $this->helper->updateQuotePrice($quote, $rateapiResponseArray, $couponCode);
            }
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ . ' No data being returned from rate api.');
        }
    }

    /**
     * Save Pickup Time
     */
    public function savePickupTime($quote, $requestPostData)
    {
        if (!empty($requestPostData->addressInformation->pickup_location_date)) {
            $pickupTime = $requestPostData->addressInformation->pickup_location_date;
            $pickupTime = $this->helper->updateDateTimeFormat($pickupTime);
            $quote->setData('estimated_pickup_time', $pickupTime);
        }

        $quote->save($quote);
    }

    /**
     * Save Quote Address
     */
    public function saveQuoteAddress($quoteId, $requestPostData)
    {
        $alternateContactInformation = [];
        $contactInformation = $this->getContactDetails($requestPostData);
        $isAlternatePickupPersonOpted = $requestPostData->contactInformation->isAlternatePerson ?? null;
        if ($isAlternatePickupPersonOpted) {
            $alternateContactInformation = $this->getAlternateContact($requestPostData);
        }

        $methodPickId = $requestPostData->addressInformation->shipping_detail->method_title ?? '';
        $shipPriceIncTax = $requestPostData->addressInformation->shipping_detail->price_incl_tax ?? '';
        $shippingCarrierCode = $requestPostData->addressInformation->shipping_detail->carrier_code ?? '';
        $shippingMethodCode = $requestPostData->addressInformation->shipping_detail->method_code ?? '';

        $quoteShip = $this->address->getCollection()->addFieldToFilter('quote_id', ["eq" => $quoteId]);

        if (!empty($shippingCarrierCode) && !empty($shippingMethodCode) && !empty($methodPickId)) {
            // B-1084159 | Save pickup address in DB from location Id
            $pickupAddress = $this->getPickupAddress($shippingMethodCode, $methodPickId);

            foreach ($quoteShip as $item) {
                $item->setShippingMethod($shippingCarrierCode . '_' . $shippingMethodCode);
                $item->setShippingDescription($methodPickId);
                $item->setBaseShippingAmount($shipPriceIncTax);
                $item->setShippingAmount($shipPriceIncTax);

                $this->setQuoteItemsData(
                    $isAlternatePickupPersonOpted,
                    $item,
                    $alternateContactInformation,
                    $contactInformation,
                    $pickupAddress
                );
                $item->save();
            }
        }
    }

    /**
     * Set Quote Address Items data
     */
    public function setQuoteItemsData(
        $isAlternatePickupPersonOpted,
        $item,
        $alternateContactInformation,
        $contactInformation,
        $pickupAddress
    ) {
        // B-1084159 | Save pickup address in DB from location Id
        if ($pickupAddress) {
            $item->setPickupAddress($pickupAddress);
        }
        if ($item->getAddressType() == 'shipping' || $item->getAddressType() == 'billing') {
            $item->setFirstname($contactInformation[self::FIRST_NAME]);
            $item->setLastname($contactInformation[self::LAST_NAME]);
            $item->setEmail($contactInformation[self::EMAIL]);
            $item->setTelephone($contactInformation[self::NUMBER]);
            $item->setExtNo($contactInformation[self::EXT_NO]);
        }
        if ($isAlternatePickupPersonOpted) {
            $altPickupEmailToggle = $this->toggleConfig->getToggleConfigValue('explorers_order_email_alternate_pick_up_person');
            if ((!$this->helper->isCommercialCustomer() ||
                $this->helper->isSdeCustomer()) || $altPickupEmailToggle
                ) {
                if ($item->getAddressType() == 'shipping') {
                    $item->setFirstname($alternateContactInformation[self::AFIRST_NAME]);
                    $item->setLastname($alternateContactInformation[self::ALAST_NAME]);
                    $item->setEmail($alternateContactInformation[self::A_EMAIL]);
                    $item->setTelephone($alternateContactInformation[self::A_NUMBER]);
                    $item->setExtNo($alternateContactInformation[self::A_EXT_NO]);
                }
            } elseif (!$altPickupEmailToggle) {
                $item->setFirstname($alternateContactInformation[self::AFIRST_NAME]);
                $item->setLastname($alternateContactInformation[self::ALAST_NAME]);
                $item->setEmail($alternateContactInformation[self::A_EMAIL]);
                $item->setTelephone($alternateContactInformation[self::A_NUMBER]);
                $item->setExtNo($alternateContactInformation[self::A_EXT_NO]);
            }
        }
    }

    /**
     * Set Shipping Data in Checkout Session
     */
    public function setShippingDataInCheckoutSession($requestData)
    {
        $methodPickId = $shipPriceIncTax = $shippingCarrierCode = $shippingMethodCode = '';

        if (isset($requestData[self::SHIPPING_INFORMATION]) && isset($requestData['quoteCreation']['quoteId'])) {
            $shippingMethodCode = $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]
                ['shipping_method_code'] ?? '';
            $shippingCarrierCode = $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]
                ['shipping_carrier_code'] ?? '';
            $methodPickId = $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['method_title'] ?? '';

            $this->checkoutSession->setCustomShippingMethodCode($shippingMethodCode);
            $this->checkoutSession->setCustomShippingCarrierCode($shippingCarrierCode);
            $this->checkoutSession->setCustomShippingTitle($methodPickId);
            $this->quoteDataHelper->unsetAddressInformation(
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]
            );
        }
    }

    /**
     * Set Contact Info in Quote
     */
    public function setQuoteContactInformation($quote, $requestPostData)
    {
        $contactInformation = $this->getContactDetails($requestPostData);

        //Check if user opted for alternate pickup person information
        $isAlternatePickupPersonOpted = $requestPostData->contactInformation->isAlternatePerson ?? null;

        //Set Alternate Contact in Checkout Session
        $this->setAlternatePickupInSession($isAlternatePickupPersonOpted);

        $quote->setData('customer_firstname', $contactInformation[self::FIRST_NAME]);
        $quote->setData('customer_lastname', $contactInformation[self::LAST_NAME]);
        $quote->setData('customer_email', $contactInformation[self::EMAIL]);
        $quote->setData('customer_telephone', $contactInformation[self::NUMBER]);
        $quote->setData(self::EXT_NO, $contactInformation[self::EXT_NO]);
        $quote->setData('is_alternate_pickup', $isAlternatePickupPersonOpted);

        if ($isAlternatePickupPersonOpted &&
            !$this->helper->isCommercialCustomer() ||
            $this->helper->isSdeCustomer()
            ) {
            $quote->getShippingAddress()->setSameAsBilling(false);
        }
    }

    /**
     * Get Pickup Address
     */
    public function getPickupAddress($shippingMethodCode, $methodPickId)
    {
        $pickupAddress = null;
        if ($shippingMethodCode == 'PICKUP' && $methodPickId) {
            $pickupAddressResponse = $this->shipToHelper->getAddressByLocationId($methodPickId);
            if (isset($pickupAddressResponse['success']) && $pickupAddressResponse['success'] == 1) {
                $pickupAddress = $pickupAddressResponse['address'];
            }
        }

        return $pickupAddress;
    }

    /**
     * Utility function to map contact details
     *
     * @param Object $requestData
     */
    public function getContactDetails($requestData)
    {
        $contactInformation = [
            self::FIRST_NAME => $requestData->contactInformation->contact_fname,
            self::LAST_NAME => $requestData->contactInformation->contact_lname,
            self::EMAIL => $requestData->contactInformation->contact_email,
            self::NUMBER => $requestData->contactInformation->contact_number,
        ];

        $contactNumber = explode(" ", $requestData->contactInformation->contact_number);
        if (!empty($contactNumber)) {
            $contactNumber = $contactNumber[0];
            $contactInformation[self::NUMBER] = $contactNumber;
        }
        $contactInformation[self::EXT_NO] = $requestData->contactInformation->contact_ext;

        return $contactInformation;
    }

    /**
     * Utility function to get alternate contact details
     *
     * @param Object $requestData
     */
    public function getAlternateContact($requestData)
    {
        $alternateContactInformation = [
            self::AFIRST_NAME => $requestData->contactInformation->alternate_fname,
            self::ALAST_NAME => $requestData->contactInformation->alternate_lname,
            self::A_EMAIL => $requestData->contactInformation->alternate_email,
            self::A_NUMBER => $requestData->contactInformation->alternate_number,
        ];

        $alternateContactInformation[self::A_EXT_NO] = $requestData->contactInformation->alternate_ext;

        return $alternateContactInformation;
    }

    /**
     * get Shipping Information Data
     */
    public function getShippingInformationData($requestPostData)
    {
        $regionCode = $requestPostData->addressInformation->pickup_location_state;
        $countryCode = $requestPostData->addressInformation->pickup_location_country;
        $regionId = $this->region->loadByCode($regionCode, $countryCode)->getId();
        $customer = $this->customerSession->getCustomer();

        return [
            self::ADDRESS_INFORMATION => [
                'shipping_address' => [
                    self::REGION => $requestPostData->addressInformation->pickup_location_state,
                    'region_id' => $regionId,
                    'region_code' => $requestPostData->addressInformation->pickup_location_state,
                    'country_id' => $requestPostData->addressInformation->pickup_location_country,
                    'street' => [
                        0 => $requestPostData->addressInformation->pickup_location_street,
                    ],
                    'postcode' => $requestPostData->addressInformation->pickup_location_zipcode,
                    'city' => $requestPostData->addressInformation->pickup_location_city,
                    self::FIRST_NAME => $customer->getFirstname(),
                    self::LAST_NAME => $customer->getLastname(),
                    self::EMAIL => $customer->getEmail(),
                    'telephone' => $customer->getContactNumber(),
                ],
                'billing_address' => [
                    self::REGION => $requestPostData->addressInformation->pickup_location_state,
                    'region_id' => $regionId,
                    'region_code' => $requestPostData->addressInformation->pickup_location_state,
                    'country_id' => $requestPostData->addressInformation->pickup_location_country,
                    'street' => [
                        0 => $requestPostData->addressInformation->pickup_location_street,
                    ],
                    'postcode' => $requestPostData->addressInformation->pickup_location_zipcode,
                    'city' => $requestPostData->addressInformation->pickup_location_city,
                    self::FIRST_NAME => $customer->getFirstname(),
                    self::LAST_NAME => $customer->getLastname(),
                    self::EMAIL => $customer->getEmail(),
                    'telephone' => $customer->getContactNumber(),
                ],
                'shipping_carrier_code' => $requestPostData->addressInformation->shipping_detail->carrier_code,
                'shipping_method_code' => $requestPostData->addressInformation->shipping_detail->method_code,
                'carrier_title' => $requestPostData->addressInformation->shipping_detail->carrier_title,
                'method_title' => $requestPostData->addressInformation->shipping_detail->method_title,
                'amount' => $requestPostData->addressInformation->shipping_detail->amount,
                'price_excl_tax' => $requestPostData->addressInformation->shipping_detail->price_excl_tax,
                'price_incl_tax' => $requestPostData->addressInformation->shipping_detail->price_incl_tax,
            ],
        ];
    }

    /**
     * Clear shipping data from checkout session
     */
    public function clearShippingDataAndCheckoutSession($quote)
    {
        $this->checkoutSession->unsCustomShippingMethodCode();
        $this->checkoutSession->unsCustomShippingCarrierCode();
        $this->checkoutSession->unsCustomShippingTitle();
        $this->checkoutSession->unsCustomShippingPrice();

        if ($quote->getShippingAddress()) {
            $qId = $quote->getId();
            $qShipmentData = $quote->getShippingAddress()->getData();
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' ePro quote address_after -- ' .
            json_encode($qShipmentData) . '-- for quote id ' . $qId);
        }

        if ($this->toggleConfig->getToggleConfigValue(self::XML_PATH_D237618_TOGGLE)) {
            $this->checkoutSession->clearQuote();
            $this->checkoutSession->setLoadInactive(false);
            $this->checkoutSession->replaceQuote($this->checkoutSession->getQuote()->save());
            $this->checkoutSession->unsAll();
            $this->checkoutSession->clearStorage();
        }

    }

    /**
     * Validate request Data
     */
    public function validateRequestData($quote, $requestPostData, $isSelfRegCustomer)
    {
        $isValidRequest = $this->quoteDataHelper->isValidateShippingDetailQuoteRequest($requestPostData);
        if ($this->helper->isCommercialCustomer() && !$isSelfRegCustomer && !$isValidRequest) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Shipping information is missing from request data for Quote ID: ' . $quote->getId());

            return ['error' => '1', 'url' => '',
                'message' => 'Shipping information is missing from request data. Please try again.'];
        }

        $isValidContactInformationRequest = $this->quoteDataHelper->isValidContactInformation($requestPostData);
        if (!$this->helper->isCommercialCustomer() &&
            !$isValidContactInformationRequest
            ) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Contact information is missing from request data for Quote ID: ' . $quote->getId());

            return ['error' => '1', 'url' => '',
                'message' => 'Contact information is missing from request data. Please try again.'];
        }

        return false;
    }

    /**
     * Set Alternate Pickup in Checkout Session
     */
    public function setAlternatePickupInSession($isAlternatePickupPerson) : void
    {
        if (!empty($isAlternatePickupPerson)) {
            if (!empty($this->checkoutSession->getAlternateContact())) {
                $this->checkoutSession->unsAlternateContact();
            }
            $this->checkoutSession->setAlternatePickupPerson($isAlternatePickupPerson);
        } else {
            if (!empty($this->checkoutSession->getAlternatePickupPerson())) {
                // If user is opting without alternate pickup once he has selected alter contact
                $this->checkoutSession->unsAlternatePickupPerson();
            }
        }
    }
}
