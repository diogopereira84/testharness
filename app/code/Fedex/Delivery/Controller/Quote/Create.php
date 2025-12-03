<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Delivery\Controller\Quote;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Delivery\Helper\Data;
use Magento\Quote\Model\Quote\Item;
use Psr\Log\LoggerInterface;
use Fedex\Purchaseorder\Model\QuoteCreation;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressExtensionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\QuoteDataHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;
use Magento\Quote\Model\Quote;

/**
 * Create Controller
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Create extends \Magento\Framework\App\Action\Action
{
    public const SHIPPING_INFORMATION = 'shippingInformation';
    public const ADDRESS_INFORMATION = 'addressInformation';
    public const SHIPPING_ADDRESS = 'shipping_address';
    public const SHIPPING_DETAIL = 'shipping_detail';
    public const PRODUCTION_LOCATION = 'productionLocation';
    public const MOCK_FEDEX_CARRIERCODE = 'freeshipping';
    public const MOCK_FEDEX_METHODCODE = 'freeshipping';
    public const FEDEX_PICKUP_METHOD = 'fedexshipping_PICKUP';


    /**
     * @param Context $context
     * @param CartRepositoryInterface $quoteRepository
     * @param Address $address
     * @param CartFactory $cartFactory
     * @param CheckoutSession $checkoutSession
     * @param CustomerSession $customerSession
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param QuoteCreation $quoteCreation
     * @param AddressRepositoryInterface $addressRepository
     * @param JsonFactory $resultJsonFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param SelfReg $selfregHelper
     * @param QuoteDataHelper $quoteDataHelper
     * @param ToggleConfig $toggleConfig
     * @param QuoteHelper $quoteHelper
     * @param CartItemRepositoryInterface $cartItemRepository
     * @param InBranchValidation $inBranchValidation
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        protected Context $context,
        protected CartRepositoryInterface $quoteRepository,
        protected Address $address,
        protected CartFactory $cartFactory,
        protected CheckoutSession $checkoutSession,
        protected CustomerSession $customerSession,
        protected Data $helper,
        protected LoggerInterface $logger,
        protected QuoteCreation $quoteCreation,
        protected AddressRepositoryInterface $addressRepository,
        protected JsonFactory $resultJsonFactory,
        protected CompanyRepositoryInterface $companyRepository,
        protected SelfReg $selfregHelper,
        protected QuoteDataHelper $quoteDataHelper,
        protected ToggleConfig $toggleConfig,
        private QuoteHelper $quoteHelper,
        private CartItemRepositoryInterface $cartItemRepository,
        private InBranchValidation $inBranchValidation,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper
    ) {
        parent::__construct($context);
    }

    /**
     * Save or create Quote via the shipping address
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Exception if Error in creating the quote
     */
    public function execute()
    {
        $quote = $this->cartFactory->create()->getQuote();
        $requestPostData = $this->getRequest()->getPost('data');

        $resultJson = $this->resultJsonFactory->create();
        $isEproCustomer = $this->helper->isEproCustomer();
        $isSelfRegCustomer = $this->selfregHelper->isSelfRegCustomer();
        $isAutoCartTransmissiontoERPEnabled = $this->helper->isAutoCartTransmissiontoERPToggleEnabled();
        $isEproUploadToFeatureToggleEnabled = $this->toggleConfig->getToggleConfigValue(
            'explorers_epro_upload_to_quote'
        );
        $invalidRequest = $this->validateRequestData($quote, $isEproCustomer, $requestPostData, $isSelfRegCustomer);
        if ($invalidRequest) {
            return $resultJson->setData($invalidRequest);
        }

        if ($isEproUploadToFeatureToggleEnabled && $isEproCustomer && !$isSelfRegCustomer) {
            $quote->setData('is_epro_quote', 1);
        }

        // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
        $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
        $locationIds = $this->checkoutSession->getLocationIds() !== null
        ? $this->checkoutSession->getLocationIds() : [];

        $productionLocationId = null;
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') && $toggleD192068FixEnabled) {
            $customPostData = json_decode((string)$requestPostData, true);
            $shippingMethodCodeLocation = isset($customPostData[self::ADDRESS_INFORMATION]['shipping_method_code']) ? $customPostData[self::ADDRESS_INFORMATION]['shipping_method_code']:'';

            if ($shippingMethodCodeLocation && !empty($locationIds) && array_key_exists($shippingMethodCodeLocation, $locationIds)) {
                $productionLocationId = $locationIds[$shippingMethodCodeLocation];
                $this->checkoutSession->setProductionLocationId($productionLocationId);
            }

            if ($this->checkoutSession->getProductionLocationId() != null
                && $this->checkoutSession->getProductionLocationId() != ''
            ) {
                $quote->setProductionLocationId($this->checkoutSession->getProductionLocationId());
            }
        }

        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') && $isEproCustomer && !$isSelfRegCustomer) {
            $this->setPreferredLocation($quote, $requestPostData);
        }

        $requestPostData = json_decode($requestPostData);

        /**
         * Set Shipping Method as Free Shipping if Marketplace only cart
         * This is needed so that order can be created.
         * Actual Marketplace shipping method is stored against quote item
         */
        if ($this->quoteHelper->isFullMiraklQuote($quote)) {
            $requestPostData->addressInformation->shipping_method_code = SELF::MOCK_FEDEX_METHODCODE;
            $requestPostData->addressInformation->shipping_carrier_code = SELF::MOCK_FEDEX_CARRIERCODE;
        } elseif ($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote)) {
            /**
             * Save shipping address in quote item additional information if mixed order and 1P is store pick-up
             */
            if ($this->toggleConfig->getToggleConfigValue('tigers_d185985')) {
                $shippingMethod = $quote->getShippingAddress()->getShippingMethod() ?? (
                    $quote->getBillingAddress()->getShippingMethod() ?? ''
                );
            } else {
                $shippingMethod = $quote->getShippingAddress()->getShippingMethod() ?? '';
            }
            if ($shippingMethod === SELF::FEDEX_PICKUP_METHOD) {
                if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                    $items = $quote->getAllVisibleItems();
                }else{
                    $items = $quote->getAllItems();
                }
                foreach ($items as $quoteItem) {
                    if ($quoteItem->getMiraklOfferId()) {
                        $additionalData = $quoteItem->getAdditionalData();
                        if ($additionalData) {
                            $additionalData = json_decode($quoteItem->getAdditionalData(), true);
                            if (isset($additionalData['mirakl_shipping_data'])) {
                                $additionalData['mirakl_shipping_data']['address'] = json_decode(json_encode($requestPostData->addressInformation->shipping_address), true);
                                $stateOrProvCode = $this->quoteDataHelper->getStateCode($requestPostData);
                                $additionalData['mirakl_shipping_data']['address']['region'] = $stateOrProvCode;
                                $quoteItem->setAdditionalData(json_encode($additionalData));
                                if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                                    $quoteItem->save();
                                }
                                else{
                                    $this->cartItemRepository->save($quoteItem);
                                }

                                $stateOrProvCode = $this->quoteDataHelper->getStateCode($requestPostData);
                                $returnData = $this->getReturnDataArray(
                                    $isEproCustomer,
                                    $isSelfRegCustomer,
                                    $quote,
                                    null,
                                    $stateOrProvCode
                                );

                                return $resultJson->setData($returnData);
                            }
                        }
                    }
                }
            }
            /**
             * Copy First Party shipping methods if mixed cart when both 1P and 3P are shipping
             */
            if (isset($requestPostData->addressInformation->shipping_detail->item_id)) {
                if ($this->toggleConfig->getToggleConfigValue('tigers_d185985')) {
                    $requestPostData->addressInformation->shipping_method_code = $requestPostData->firstPartyMethod->method_code ?? '';
                    $requestPostData->addressInformation->shipping_carrier_code = $requestPostData->firstPartyMethod->carrier_code ?? '';
                    $requestPostData->addressInformation->shipping_detail->carrier_code = $requestPostData->firstPartyMethod->carrier_code ?? '';
                    $requestPostData->addressInformation->shipping_detail->method_code = $requestPostData->firstPartyMethod->method_code ?? '';
                    $requestPostData->addressInformation->shipping_detail->carrier_title = $requestPostData->firstPartyMethod->carrier_title ?? '';
                    $requestPostData->addressInformation->shipping_detail->method_title = $requestPostData->firstPartyMethod->method_title ?? '';
                    $requestPostData->addressInformation->shipping_detail->amount = $requestPostData->firstPartyMethod->amount ?? '';
                    $requestPostData->addressInformation->shipping_detail->base_amount = $requestPostData->firstPartyMethod->base_amount ?? '';
                    $requestPostData->addressInformation->shipping_detail->price_incl_tax = $requestPostData->firstPartyMethod->price_incl_tax ?? '';
                    $requestPostData->addressInformation->shipping_detail->price_excl_tax = $requestPostData->firstPartyMethod->price_excl_tax ?? '';
                } else {
                    $requestPostData->addressInformation->shipping_method_code = $requestPostData->firstPartyMethod->method_code;
                    $requestPostData->addressInformation->shipping_carrier_code = $requestPostData->firstPartyMethod->carrier_code;
                    $requestPostData->addressInformation->shipping_detail->carrier_code = $requestPostData->firstPartyMethod->carrier_code;
                    $requestPostData->addressInformation->shipping_detail->method_code = $requestPostData->firstPartyMethod->method_code;
                    $requestPostData->addressInformation->shipping_detail->carrier_title = $requestPostData->firstPartyMethod->carrier_title;
                    $requestPostData->addressInformation->shipping_detail->method_title = $requestPostData->firstPartyMethod->method_title;
                    $requestPostData->addressInformation->shipping_detail->amount = $requestPostData->firstPartyMethod->amount;
                    $requestPostData->addressInformation->shipping_detail->base_amount = $requestPostData->firstPartyMethod->base_amount;
                    $requestPostData->addressInformation->shipping_detail->price_incl_tax = $requestPostData->firstPartyMethod->price_incl_tax;
                    $requestPostData->addressInformation->shipping_detail->price_excl_tax = $requestPostData->firstPartyMethod->price_excl_tax;
                }
            }
        }

        $company = $requestPostData->addressInformation->shipping_address->company ?? null;
        $isAlternate =  $requestPostData->addressInformation->shipping_address->is_alternate ?? '';

        //Set Alternate Contact in Checkout Session
        $this->setAlternateContactInSession($isAlternate);

        $fedexShipAccountNumber = $requestPostData->addressInformation->shipping_detail->fedexShipAccountNumber ?? '';
        $fedexShipReferenceId =  $requestPostData->addressInformation->shipping_detail->fedexShipReferenceId ?? '';
        if ($this->marketplaceCheckoutHelper->isCustomerShippingAccount3PEnabled()
            && $this->marketplaceCheckoutHelper->isVendorSpecificCustomerShippingAccountEnabled()) {
            $this->saveFedexShippingAccount($quote, $fedexShipAccountNumber, $fedexShipReferenceId);
        }

        $shippingData = [];
        if (!empty($requestPostData)) {
            $methodTitle = $requestPostData->addressInformation->shipping_detail->method_title;
            if (!$isEproCustomer) {
                $methodTitle = $requestPostData->addressInformation->shipping_detail->carrier_title . ' - '
                . $requestPostData->addressInformation->shipping_detail->method_title;
            }
            $shippingData = $this->quoteDataHelper->getShippingData($requestPostData, $methodTitle);
        }

        $stateOrProvCode = $this->quoteDataHelper->getStateCode($requestPostData);
        $isCommercialCustomer = $this->helper->isCommercialCustomer();
        $isSdeCustomer = $this->helper->isSdeCustomer();
        $contactInformation = $this->quoteDataHelper->getContactDetails(
            $requestPostData,
            $isAlternate,
            $isCommercialCustomer,
            $isSdeCustomer
        );

        $quoteId = $quote->getId();

        $negotiableQuoteCreateData = $this->quoteDataHelper->getNegotiableQuoteCreateData($quoteId, $shippingData);

        // to save shipping details
        $shippingCost = 0;
        $negotiableQuoteData = json_encode($negotiableQuoteCreateData);
        $requestData = json_decode($negotiableQuoteData, true);
        if (isset($requestData[self::SHIPPING_INFORMATION]) && isset($requestData['quoteCreation']['quoteId'])) {
            $this->setShippingMethoodDetailsInCheckoutSession($requestData);
            $shippingCost = $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['amount'];

            $this->quoteDataHelper->unsetAddressInformation(
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]
            );
        }
        $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]
            [self::SHIPPING_ADDRESS]['company'] = $company;

        $quote = $this->quoteRepository->getActive($quoteId);

        // If explorers_d_165914_fix toggle is on then save shipping information with quote
        // as well when we don't get values from checkout session
        $this->quoteDataHelper->setShippingInfo($quote, $requestData);
        $this->quoteDataHelper->setQuoteData($quote, $contactInformation);
        $this->quoteDataHelper->setAlternateAddress(
            $quote,
            $requestPostData,
            $isAlternate,
            $isCommercialCustomer,
            $isSdeCustomer
        );

        $quote->setData('is_alternate', $isAlternate);

        if (isset($requestData[self::SHIPPING_INFORMATION])) {
            $this->saveShippingAccountNumber(
                $requestData[self::SHIPPING_INFORMATION],
                $quote,
                $fedexShipAccountNumber
            );
        } else {
            $quote->setData('fedex_ship_account_number', $fedexShipAccountNumber);
        }
        $quote->setData('fedex_ship_reference_id', $fedexShipReferenceId);

        $this->updateQuotePrices($quote, $requestPostData);
        $notification = null;
        $isNegotiatedQuoteExist = true;

        if ($isEproCustomer && !$isSelfRegCustomer) {
            // Create Negotiable Quote.
            if ($isEproUploadToFeatureToggleEnabled) {
                if (!$this->quoteDataHelper->checkNegotiableQuoteExistingForQuote($quoteId)) {
                    $this->quoteDataHelper->createNegotiableQuote($quote, $this->quoteRepository, $requestData);
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
        }
        $couponCode = $quote->getData('coupon_code');

        // Save shipping address
        if ($this->helper->isCommercialCustomer() || !$this->helper->getCustomer()) {
            $this->quoteCreation->saveShippingAddress($requestData[self::SHIPPING_INFORMATION], $quoteId);
        }

        if ($this->toggleConfig->getToggleConfigValue('explorers_address_classification_fix')) {
            $isResidenceShipping = false;
            if (isset($requestPostData->addressInformation->shipping_address) && isset($requestPostData->addressInformation->shipping_address->customAttributes)) {
                $customAttributesObj = $requestPostData->addressInformation->shipping_address->customAttributes;
                $customAttributes = json_decode(json_encode($customAttributesObj), true);
                foreach ($customAttributes as $attribute) {
                    if ($attribute['attribute_code'] === 'residence_shipping') {
                        $isResidenceShipping = $attribute['value'];
                    }
                }
            }
            $quote->getBillingAddress()->setIsResidenceShipping($isResidenceShipping);
            $quote->getShippingAddress()->setIsResidenceShipping($isResidenceShipping);
        }

        $quote->getShippingAddress()->setCompany($company);

        if ($this->toggleConfig->getToggleConfigValue('techtitans_208009_promo_code_fix') && $couponCode) {
            $quote->setData('coupon_code', $couponCode);
        }

        $quote->save();

        $this->saveQuoteAddress($quoteId, $contactInformation, $company, $requestPostData);

        $returnData = $this->getReturnDataArray(
            $isEproCustomer,
            $isSelfRegCustomer,
            $quote,
            $notification,
            $stateOrProvCode
        );

        if ($isEproUploadToFeatureToggleEnabled && $isNegotiatedQuoteExist && $isEproCustomer && !$isSelfRegCustomer) {
            $eproNegotiableDeclinedQuote = $quote->getExtensionAttributes()->getNegotiableQuote()->getStatus();
            if ($eproNegotiableDeclinedQuote == NegotiableQuoteInterface::STATUS_DECLINED) {
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__. ':Epro negotiable quote was declined for this quoteId: '.$quoteId
                );
                $quoteStatus = NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN;
                $this->quoteDataHelper->updateEproQuoteStatusByKey($quoteId, $quoteStatus);
                $this->logger->info(
                    __METHOD__ . ':' . __LINE__.
                        ':Finally Epro upload to quote converted into normal quote for this quoteId: '.$quoteId
                );
            }

            $quote->setData('sent_to_erp', 1);
            $this->quoteDataHelper->updateEproNegotiableQuote($quote);
            $quote->getExtensionAttributes()->getNegotiableQuote()->save();
            $quote->setIsActive(0);
            $quote->save();
        }

        return $resultJson->setData($returnData);
    }

    /**
     * Set Shipping methods detail in checkout session
     */
    public function setShippingMethoodDetailsInCheckoutSession($requestData)
    {
        $shippingMethodCode = isset($requestData[self::SHIPPING_INFORMATION]
                [self::ADDRESS_INFORMATION]['shipping_method_code']) ?
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['shipping_method_code'] : '';
        $shippingCarrierCode = isset($requestData[self::SHIPPING_INFORMATION]
            [self::ADDRESS_INFORMATION]['shipping_carrier_code']) ?
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['shipping_carrier_code'] : '';
        $shipMethodTitle = isset($requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['method_title']) ?
            $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['method_title'] : '';

        $this->checkoutSession->setCustomShippingMethodCode($shippingMethodCode);
        $this->checkoutSession->setCustomShippingCarrierCode($shippingCarrierCode);
        $this->checkoutSession->setCustomShippingTitle($shipMethodTitle);
    }

    /**
     * Clear checkout session data in case of Epro without self reg
     */
    public function checkoutSaveAddressAndClearSession($quote, $requestData)
    {
        $requestData = json_decode($requestData, true);
        $customerId = $quote->getCustomerId();
        if (isset($requestData[self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS]['saveInAddressBook']) &&
            $requestData[self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS]['saveInAddressBook'] == "1" &&
            $customerId > 0
            ) {
            $this->saveAddress($this->getRequest()->getPost('data'), $customerId);
        }
        if ($quote->getShippingAddress()) {
            $qId = $quote->getId();
            $qShipmentData = $quote->getShippingAddress()->getData();
            $this->logger->info(__METHOD__ . ':' . __LINE__ .
                ' ePro quote address_after -- ' . json_encode($qShipmentData) . '-- for quote id '. $qId);
        }
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $this->checkoutSession->unsProductionLocationId();
        }
        $this->checkoutSession->unsCustomShippingMethodCode();
        $this->checkoutSession->unsCustomShippingCarrierCode();
        $this->checkoutSession->unsCustomShippingTitle();
        $this->checkoutSession->unsCustomShippingPrice();
        $this->checkoutSession->clearQuote();
        $this->customerSession->logout()->setLastCustomerId($this->customerSession->getCustomer()->getId());

        return true;
    }

    /**
     * Set Preffered location
     */
    public function setPreferredLocation($quote, $customPostData): void
    {
        $customPostData = json_decode((string)$customPostData, true);
        $companyId = $this->customerSession->getCustomerCompany();
        $customerRepo = $this->companyRepository->get((int) $companyId);

        if (isset($customPostData[self::ADDRESS_INFORMATION][self::SHIPPING_DETAIL][self::PRODUCTION_LOCATION]) &&
            $customPostData[self::ADDRESS_INFORMATION][self::SHIPPING_DETAIL][self::PRODUCTION_LOCATION] > 0
            ) {
            $preperredLocationId = $customPostData[self::ADDRESS_INFORMATION]
                [self::SHIPPING_DETAIL][self::PRODUCTION_LOCATION];
            $this->checkoutSession->setProductionLocationId($preperredLocationId);
        }
        if ($customerRepo->getAllowProductionLocation() == 1 &&
            $customerRepo->getProductionLocationOption() == 'recommended_location_all_locations'
            ) {
            $productionlocationid = $this->checkoutSession->getProductionLocationId();
            $quote->setProductionLocationId($productionlocationid);
        }

        //Inbranch Implementation
        if ($this->inBranchValidation->getAllowedInBranchLocation() != null) {
            $productLocationId = $this->inBranchValidation->getAllowedInBranchLocation();
            $quote->setProductionLocationId($productLocationId);
        }
        //Inbranch Implementation
    }

    /**
     * Save Quote Address
     */
    public function saveQuoteAddress($quoteId, $contactInformation, $company, $requestPostData): void
    {
        $shippingMethodCode = isset($requestPostData->addressInformation->shipping_method_code) ?
            $requestPostData->addressInformation->shipping_method_code : '';
        $shippingCarrierCode = isset($requestPostData->addressInformation->shipping_carrier_code) ?
            $requestPostData->addressInformation->shipping_carrier_code : '';
        $carrierTitle = isset($requestPostData->addressInformation->shipping_detail->carrier_title) ?
            $requestPostData->addressInformation->shipping_detail->carrier_title : '';
        $shipMethodTitle = isset($requestPostData->addressInformation->shipping_detail->method_title) ?
            $requestPostData->addressInformation->shipping_detail->method_title : '';
        $shipPriceIncTax = isset($requestPostData->addressInformation->shipping_detail->price_incl_tax) ?
            $requestPostData->addressInformation->shipping_detail->price_incl_tax : '';

        $quoteShip = $this->address->getCollection()->addFieldToFilter('quote_id', ["eq" => $quoteId]);

        if (!empty($shippingCarrierCode) && !empty($shippingMethodCode) && !empty($shipMethodTitle)) {
            foreach ($quoteShip as $item) {
                $item->setShippingMethod($shippingCarrierCode . '_' . $shippingMethodCode);
                $item->setShippingDescription($carrierTitle .' - '.$shipMethodTitle);
                $item->setBaseShippingAmount($shipPriceIncTax);
                $item->setShippingAmount($shipPriceIncTax);

                // Set Quote Item Data.
                $this->setQuoteItemsData($item, $contactInformation, $requestPostData);

                $item->setStreet($contactInformation['street']);
                $item->setPostcode($contactInformation['postcode']);
                $item->setRegion($contactInformation['region']);
                $item->setRegionId($contactInformation['region_id']);
                $item->setCountryId($contactInformation['country_id']);
                $item->setCity($contactInformation['city']);
                $item->setCompany($company);
                $item->save();
            }
        }
    }

    /**
     * Set Quote Item Data
     */
    public function setQuoteItemsData(
        $item,
        $contactInformation,
        $requestPostData
    ) {
        $customAttributes = $requestPostData->addressInformation->shipping_address->customAttributes;
        $recipientEmail = $this->quoteDataHelper->getRecipientEmail($customAttributes);
        $extNo = $this->quoteDataHelper->getRecipientPhoneExt($customAttributes);

        $item->setFirstname($contactInformation['firstName']);
        $item->setLastname($contactInformation['lastName']);
        $item->setEmail($contactInformation['email']);
        $item->setTelephone($contactInformation['number']);
        $item->setExtNo($contactInformation['ext_no']);

        if (
            $this->toggleConfig->getToggleConfigValue('tech_titans_d_213795') &&
            $this->helper->isCommercialCustomer() &&
            isset($requestPostData->addressInformation->shipping_detail->extension_attributes) &&
            isset($requestPostData->addressInformation->shipping_detail->extension_attributes->production_location)
        ) {
            $item->setProductionLocation(
                $requestPostData->addressInformation->shipping_detail->extension_attributes->production_location
            );
        }
        if (!empty($this->checkoutSession->getAlternateContact())) {
            if (!$this->helper->isCommercialCustomer() ||
                $this->helper->isSdeCustomer()
            ) {
                if ($item->getAddressType() == 'shipping') {
                    $item->setFirstname($requestPostData->addressInformation->shipping_address->firstname);
                    $item->setLastname($requestPostData->addressInformation->shipping_address->lastname);
                    $item->setEmail($recipientEmail);
                    $item->setTelephone($requestPostData->addressInformation->shipping_address->telephone);
                    $item->setExtNo($extNo);
                }
            } else {
                $item->setFirstname($contactInformation['firstName']);
                $item->setLastname($contactInformation['lastName']);
                $item->setEmail($contactInformation['email']);
                $item->setTelephone($contactInformation['number']);
                $item->setExtNo($contactInformation['ext_no']);
            }
        }
    }

    /**
     * Save customer address.
     *
     * @param  Object $requestData
     * @param  Int    $customerId
     * @return void
     */
    public function saveAddress($requestData, $customerId): void
    {
        $requestData = json_decode($requestData, true);
        if (isset($requestData[self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS])) {
            $shippingAddress = $requestData[self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS];

            $addressData = $this->quoteDataHelper->getNewAddressData($shippingAddress, $customerId);
            try {
                $this->addressRepository->save($addressData);
            } catch (Exception $exception) {
                $this->logger->error(__METHOD__ . ':' . __LINE__ .
                ' Unable to save address. ' . $exception->getMessage());
            }
        }
    }

    /**
     * Validate request Data
     */
    public function validateRequestData($quote, $isEproCustomer, $requestPostData, $isSelfRegCustomer)
    {
        if (empty($requestPostData)) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . ' There was a problem in creating quote.');
            return ['error' => '1', 'url' => '',
                'message' => 'There was a problem in creating quote. Please try again.'];
        }

        $isValidRequest = $this->quoteDataHelper->isValidateShippingDetailQuoteRequest($requestPostData);
        if ($isEproCustomer && !$isSelfRegCustomer && !$isValidRequest) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .
            ' Shipping information is missing from request data for Quote ID: ' . $quote->getId());

            return ['error' => '1', 'url' => '',
                'message' => 'Shipping information is missing from request data. Please try again.'];
        }

        return false;
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
     * Set Alternate Contact in Checkout Session
     */
    public function setAlternateContactInSession($isAlternate) : void
    {
        if (!empty($isAlternate)) {
            if (!empty($this->checkoutSession->getAlternatePickup())) {
                $this->checkoutSession->unsAlternatePickup();
            }
            $this->checkoutSession->setAlternateContact($isAlternate);
        } else {
            if (!empty($this->checkoutSession->getAlternateContact())) {
                // If user is opting without alternate contact once he has selected alter contact
                $this->checkoutSession->unsAlternateContact();
            }
        }
    }

    /**
     * Set Return Data
     */
    public function getReturnDataArray(
        $isEproCustomer,
        $isSelfRegCustomer,
        $quote,
        $notification,
        $stateOrProvCode
    )
    {
        $returnData = [];
        if ($isEproCustomer && !$isSelfRegCustomer) {
            $redirectionUrl = $this->helper->getRedirectUrl();
            $requestData = $this->getRequest()->getPost('data');

            $this->checkoutSaveAddressAndClearSession($quote, $requestData);

            $returnData = ['notification' => base64_encode((string)$notification), 'url' => $redirectionUrl];

        } else {
            $returnData = ['stateOrProvinceCode' => $stateOrProvCode];
        }

        return $returnData;
    }

    /**
     * Save Or Delete Shipping Account Number
     *
     * @param array $shippingMethodCode
     * @param object $quote
     * @param string $fedexShipAccountNumber
     * @return void
     */
    public function saveShippingAccountNumber($shippingMethodCode, $quote, $fedexShipAccountNumber) : void
    {
        $shippingMethodCode = isset($shippingMethodCode[self::ADDRESS_INFORMATION]['shipping_method_code']) ?
        $shippingMethodCode[self::ADDRESS_INFORMATION]['shipping_method_code'] : '';
        $saveShippingAccountInformation =
        $shippingMethodCode != 'LOCAL_DELIVERY_PM' &&
        $shippingMethodCode != 'LOCAL_DELIVERY_AM' ? true : false;
        if ($saveShippingAccountInformation) {
            $quote->setData('fedex_ship_account_number', $fedexShipAccountNumber);
        } else {
            $quote->setData('fedex_ship_account_number', '');
        }
    }

    /**
     * Save FedEx shipping account data to quote items.
     *
     * This updates additional_data of each applicable quote item with FedEx account and reference ID.
     *
     * @param Quote $quote
     * @param string $fedexShipAccountNumber
     * @param string $fedexShipReferenceId
     * @return void
     */
    public function saveFedexShippingAccount(
        Quote $quote,
        string $fedexShipAccountNumber,
        string $fedexShipReferenceId = ''
    ): void {
        try {
            foreach ($quote->getItems() as $item) {
                if (!$item->getMiraklOfferId()) {
                    continue;
                }

                $additionalData = $this->getDecodedAdditionalData($item);
                if (empty($additionalData['mirakl_shipping_data'])) {
                    continue;
                }

                $additionalData['mirakl_shipping_data']['fedexShipAccountNumber'] = $fedexShipAccountNumber;
                $additionalData['mirakl_shipping_data']['fedexShipReferenceId'] = $fedexShipReferenceId;
                $item->setAdditionalData(json_encode($additionalData));
            }
            $this->quoteRepository->save($quote);
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ': ' . __LINE__ . ' - Error saving quote data: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get decoded additional data as an array or empty array.
     *
     * @param Item $item
     * @return array
     */
    private function getDecodedAdditionalData(Item $item): array
    {
        $data = $item->getAdditionalData();

        if (!$data) {
            return [];
        }

        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : [];
    }
}
