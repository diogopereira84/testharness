<?php

/**
 * Copyright © Fedex Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\Delivery\Helper;

use Magento\Directory\Model\RegionFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Psr\Log\LoggerInterface;
use Fedex\Email\Helper\Data as EmailDataHelper;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Model\CommentManagementInterface;
use Magento\NegotiableQuote\Model\Quote\History;
use Magento\NegotiableQuote\Api\NegotiableQuoteItemManagementInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\B2b\Model\NegotiableQuoteManagement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * QuoteDataHelper Class for quote
 */
class QuoteDataHelper
{
    public const QUOTE_CREATION = 'quoteCreation';
    public const SHIPPING_INFORMATION = 'shippingInformation';
    public const ADDRESS_INFORMATION = 'addressInformation';
    public const SHIPPING_ADDRESS = 'shipping_address';
    public const BILLING_ADDRESS = 'billing_address';
    public const REGION = 'region';
    public const REGION_ID = 'region_id';
    public const COUNTRY_ID = 'country_id';
    public const STREET = 'street';
    public const POSTCODE = 'postcode';
    public const FIRST_NAME = 'firstname';
    public const LAST_NAME = 'lastname';
    public const EMAIL = 'email';
    public const TELEPHONE = 'telephone';
    public const CARRIER_TITLE = 'carrier_title';
    public const EXT_NO = 'ext_no';
    public const CUSTOM_ATTRIBUTES = 'customAttributes';
    public const SHIPPING_DETAIL = 'shipping_detail';
    public const MAZEGEEKS_D210584_FIX = 'mazegeeks_d_210584_fix';

    /**
     * @param RegionFactory $regionFactory
     * @param AddressInterfaceFactory $dataAddressFactory
     * @param LoggerInterface $logger
     * @param EmailDataHelper $emailHelper
     * @param NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagementInterface
     * @param CommentManagementInterface $commentManagementInterface
     * @param History $history
     * @param AdminConfigHelper $adminConfigHelper
     * @param NegotiableQuoteManagement $negotiableQuoteManagement
     * @param TimezoneInterface $timezoneInterface
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        protected RegionFactory $regionFactory,
        protected AddressInterfaceFactory $dataAddressFactory,
        protected LoggerInterface $logger,
        protected EmailDataHelper $emailHelper,
        protected NegotiableQuoteItemManagementInterface $negotiableQuoteItemManagementInterface,
        protected CommentManagementInterface $commentManagementInterface,
        protected History $history,
        private AdminConfigHelper $adminConfigHelper,
        protected NegotiableQuoteManagement $negotiableQuoteManagement,
        protected ToggleConfig $toggleConfig,
        protected TimezoneInterface $timezoneInterface
    )
    {
    }

    /**
     * Get NegotiableQuote Create Data
     */
    public function getNegotiableQuoteCreateData($quoteId, $shippingData)
    {
        return [
            self::QUOTE_CREATION => [
                'quoteId' => $quoteId,
                'quoteName' => 'Punchout Quote Creation',
                'comment' => 'Review my quote',
            ],
            self::SHIPPING_INFORMATION => $shippingData
        ];
    }

    /**
     * Get ReceipientEmail
     */
    public function getRecipientEmail($customAttributes)
    {
        $recipientEmail = '';
        foreach ($customAttributes as $attribute) {
            if ($attribute->attribute_code == 'email_id') {
                $recipientEmail = $attribute->value;
            }
        }

        return $recipientEmail;
    }

    /**
     * Get ReceipientPhoneExtension
     */
    public function getRecipientPhoneExt($customAttributes)
    {
        $ext = '';
        foreach ($customAttributes as $attribute) {
            if (trim($attribute->attribute_code) == 'ext') {
                $ext = trim($attribute->value);
            }
        }

        return $ext;
    }

    /**
     * Get Shipping Data
     */
    public function getShippingData($requestData, $methodTitle)
    {
        $customAttributes = $requestData->addressInformation->shipping_address->customAttributes;
        $recipientEmail = $this->getRecipientEmail($customAttributes);

        return [
            self::ADDRESS_INFORMATION => [
                self::SHIPPING_ADDRESS => [
                    self::REGION => isset($requestData->addressInformation->shipping_address->region) ?
                        $requestData->addressInformation->shipping_address->region : '',
                    self::REGION_ID => isset($requestData->addressInformation->shipping_address->regionId) ?
                        $requestData->addressInformation->shipping_address->regionId : '',
                    'region_code' => isset($requestData->addressInformation->shipping_address->regionCode) ?
                        $requestData->addressInformation->shipping_address->regionCode : '',
                    self::COUNTRY_ID => isset($requestData->addressInformation->shipping_address->countryId) ?
                        $requestData->addressInformation->shipping_address->countryId : '',
                    self::STREET => [
                        0 => $requestData->addressInformation->shipping_address->street[0],
                    ],
                    self::POSTCODE => $requestData->addressInformation->shipping_address->postcode,
                    'city' => $requestData->addressInformation->shipping_address->city,
                    self::FIRST_NAME => $requestData->addressInformation->shipping_address->firstname,
                    self::LAST_NAME => $requestData->addressInformation->shipping_address->lastname,
                    self::EMAIL => $recipientEmail,
                    self::TELEPHONE => $requestData->addressInformation->shipping_address->telephone,
                ],
                self::BILLING_ADDRESS => [
                    self::REGION => isset($requestData->addressInformation->billing_address->region) ?
                        $requestData->addressInformation->billing_address->region : '',
                    self::REGION_ID => isset($requestData->addressInformation->billing_address->regionId) ?
                        $requestData->addressInformation->billing_address->regionId : '',
                    'region_code' => isset($requestData->addressInformation->billing_address->regionCode) ?
                        $requestData->addressInformation->billing_address->regionCode : '',
                    self::COUNTRY_ID => isset($requestData->addressInformation->billing_address->countryId) ?
                        $requestData->addressInformation->billing_address->countryId : '',
                    self::STREET => [
                        0 => $requestData->addressInformation->billing_address->street[0],
                    ],
                    self::POSTCODE => $requestData->addressInformation->billing_address->postcode,
                    'city' => $requestData->addressInformation->billing_address->city,
                    self::FIRST_NAME => $requestData->addressInformation->billing_address->firstname,
                    self::LAST_NAME => $requestData->addressInformation->billing_address->lastname,
                    self::EMAIL => $recipientEmail,
                    self::TELEPHONE => $requestData->addressInformation->billing_address->telephone,
                ],
                'shipping_carrier_code' => $requestData->addressInformation->shipping_carrier_code,
                'shipping_method_code' => $requestData->addressInformation->shipping_method_code,
                self::CARRIER_TITLE => $requestData->addressInformation->shipping_detail->carrier_title,
                'method_title' => $methodTitle,
                'amount' => $requestData->addressInformation->shipping_detail->amount,
                'price_excl_tax' => $requestData->addressInformation->shipping_detail->price_excl_tax,
                'price_incl_tax' => $requestData->addressInformation->shipping_detail->price_incl_tax,
            ],
        ];
    }

    /**
     * Get State/Province Code
     */
    public function getStateCode($requestData)
    {
        $stateOrProvCode = "";
        if (isset($requestData->addressInformation->shipping_address->regionId)) {
            $stateOrProvCodeObject = $this->regionFactory->create()
                ->load($requestData->addressInformation->shipping_address->regionId);

            if (isset($stateOrProvCodeObject)) {
                $stateOrProvCode = $stateOrProvCodeObject->getCode();
            }
        }

        return $stateOrProvCode;
    }

    /**
     * Unset AddressInformation
     */
    public function unsetAddressInformation($shipAddressInfo)
    {
        unset($shipAddressInfo[self::CARRIER_TITLE]);
        unset($shipAddressInfo['method_title']);
        unset($shipAddressInfo['amount']);
        unset($shipAddressInfo['price_excl_tax']);
        unset($shipAddressInfo['price_incl_tax']);
    }

    /**
     * Set Quote Data
     */
    public function setQuoteData($quote, $contactInformation)
    {
        $quote->setData('customer_firstname', $contactInformation['firstName']);
        $quote->setData('customer_lastname', $contactInformation['lastName']);
        $quote->setData('customer_email', $contactInformation[self::EMAIL]);
        $quote->setData('customer_telephone', $contactInformation['number']);
        $quote->setData(self::EXT_NO, $contactInformation[self::EXT_NO]);
    }

    /**
     * Set and get New address Data to be saved in Epro
     */
    public function getNewAddressData($shippingAddress, $customerId)
    {
        $firstName = (isset($shippingAddress[self::FIRST_NAME]) ? $shippingAddress[self::FIRST_NAME] : null);
        $lastName = (isset($shippingAddress[self::LAST_NAME]) ? $shippingAddress[self::LAST_NAME] : null);
        $telephone = (isset($shippingAddress[self::TELEPHONE]) ? $shippingAddress[self::TELEPHONE] : null);
        $street[0] = (isset($shippingAddress[self::STREET][0]) ? $shippingAddress[self::STREET][0] : null);
        if (isset($shippingAddress[self::STREET][1])) {
            $street[1] = $shippingAddress[self::STREET][1];
        }
        $city = (isset($shippingAddress['city']) ? $shippingAddress['city'] : null);
        $countryId = (isset($shippingAddress['countryId']) ? $shippingAddress['countryId'] : null);
        $regionId = (isset($shippingAddress['regionId']) ? $shippingAddress['regionId'] : null);
        $postcode = (isset($shippingAddress[self::POSTCODE]) ? $shippingAddress[self::POSTCODE] : null);
        $company = (isset($shippingAddress['company']) ? $shippingAddress['company'] : null);
        
        $address = $this->dataAddressFactory->create();

        $address->setFirstname($firstName);
        $address->setLastname($lastName);
        $address->setTelephone($telephone);
        $address->setStreet($street);
        $address->setCity($city);
        $address->setCountryId($countryId);
        $address->setPostcode($postcode);
        $address->setRegionId($regionId);
        $address->setCompany($company);
        $address->setIsDefaultShipping(0);
        $address->setIsDefaultBilling(0);
        $address->setCustomerId($customerId);
        
        if (isset($shippingAddress[self::CUSTOM_ATTRIBUTES]) && !empty($shippingAddress[self::CUSTOM_ATTRIBUTES])) {
            foreach ($shippingAddress[self::CUSTOM_ATTRIBUTES] as $customAtt) {
                $code = $customAtt['attribute_code'];
                $value = $customAtt['value'];
                $address->setCustomAttribute($code, $value);
            }
        }

        return $address;
    }

    /**
     * Create Negotiable Quote
     */
    public function createNegotiableQuote(
        $quote,
        $quoteRepository,
        $requestData,
        $uploadToQuote = [],
        $isNegotiated = false,
        $fuseBidding = false
    ): void {
        $quoteId = $quote->getId();
        $negotiableQuote = $quote->getExtensionAttributes()->getNegotiableQuote();
        $isUploadToQuote = false;
        if (!empty($uploadToQuote) && isset($uploadToQuote["isUploadToQuote"]) || $fuseBidding) {
            $isUploadToQuote = true;
            $negotiableQuote->setQuoteId($quote->getId())
                ->setIsRegularQuote(true)
                ->setAppliedRuleIds($quote->getAppliedRuleIds());
            if (!$isNegotiated) {
                $negotiableQuote->setStatus(NegotiableQuoteInterface::STATUS_CREATED);
            }
            $this->checkifuploadToQuote($quote, $uploadToQuote, $negotiableQuote);
            $this->checkiffuseBidding($quote, $fuseBidding, $negotiableQuote);
            if (!$negotiableQuote->getExpirationPeriod()) {
                $quoteExpirationDate = $this->adminConfigHelper->getExpiryDate($quoteId, 'Y-m-d');
                $negotiableQuote->setExpirationPeriod($quoteExpirationDate);
            }
            $this->negotiableQuoteManagement->updateNegotiableSnapShot($quoteId);
        } else {
            $negotiableQuote->setQuoteId($quote->getId())
                ->setIsRegularQuote(true)
                ->setAppliedRuleIds($quote->getAppliedRuleIds())
                ->setStatus(NegotiableQuoteInterface::STATUS_CREATED)
                ->setQuoteName($requestData[self::QUOTE_CREATION]['quoteName']);
        }
        // Saving quote here would preserve the quoteTotal value after successfully quote creation
        $quoteRepository->save($quote);
        if (empty($uploadToQuote['isNegotiableQuoteSi'])) {
            $this->negotiableQuoteItemManagementInterface->updateQuoteItemsCustomPrices($quoteId);
        }

        $qId = $quote->getId();
        if ($quote->getExtensionAttributes()->getNegotiableQuote()) {
            $qNegQuote = $quote->getExtensionAttributes()->getNegotiableQuote()->getData();
            $this->logger->info('ePro Negotiable quote :' . json_encode($qNegQuote) . ' for quote id: '. $qId);
        }
        if ($quote->getShippingAddress()) {
            $qShipmentData = $quote->getShippingAddress()->getData();
            $this->logger->info('ePro quote address_before: ' .
                json_encode($qShipmentData) . ' for quote id: '. $qId);
        }
        if(!$fuseBidding) {
            $this->commentManagementInterface->update(
                $quoteId,
                $requestData[self::QUOTE_CREATION]['comment']
            );
        }
        
        $this->history->createLog($quoteId);
        if (!$isUploadToQuote && !$fuseBidding) {
            $this->emailHelper->sendEmailNotification($quoteId);
        }
    }

    /**
     * Get contact detail.
     *
     * @param  Object $requestData
     * @return Array
     */
    public function getContactDetails(
        $requestData,
        $isAlternate,
        $isCommercialCustomer,
        $isSdeCustomer
    ) {
        $emailId = '';
        $contactInformation = [];
        $customAttributes = $requestData->addressInformation->shipping_address->customAttributes;
        $emailId = $this->getRecipientEmail($customAttributes);
        $extNo = $this->getRecipientPhoneExt($customAttributes);

        $firstName = null;
        $lastName = null;
        $telephone = null;
        if ($isAlternate && !$isCommercialCustomer || $isSdeCustomer) {
            if ($isSdeCustomer && !$isAlternate) {
                $firstName = $requestData->addressInformation->shipping_address->firstname;
                $lastName = $requestData->addressInformation->shipping_address->lastname;
                $telephone = $requestData->addressInformation->shipping_address->telephone;
            } else {
                $firstName = $requestData->addressInformation->shipping_address->altFirstName;
                $lastName = $requestData->addressInformation->shipping_address->altLastName;
                $emailId = $requestData->addressInformation->shipping_address->altEmail;
                $telephone = $requestData->addressInformation->shipping_address->altPhoneNumber;
                $extNo = $requestData->addressInformation->shipping_address->altPhoneNumberext;
            }
        } else {
            $firstName = $requestData->addressInformation->shipping_address->firstname;
            $lastName = $requestData->addressInformation->shipping_address->lastname;
            $telephone = $requestData->addressInformation->shipping_address->telephone;
        }
        $contactInformation = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            self::EMAIL => $emailId,
            'number' => $telephone,
            self::STREET => $requestData->addressInformation->shipping_address->street,
            self::COUNTRY_ID => $requestData->addressInformation->shipping_address->countryId,
            self::REGION_ID => $requestData->addressInformation->shipping_address->regionId,
            self::REGION => $requestData->addressInformation->shipping_address->region,
            self::POSTCODE => $requestData->addressInformation->shipping_address->postcode,
            'city' => $requestData->addressInformation->shipping_address->city
        ];
        
        $contactInformation[self::EXT_NO] = $extNo;

        return $contactInformation;
    }

    /**
     * Set alternate Address
     */
    public function setAlternateAddress(
        $quote,
        $requestData,
        $isAlternate,
        $isCommercialCustomer,
        $isSdeCustomer
    ) : void {
        $altFirstName = isset($requestData->addressInformation->shipping_address->altFirstName) ?
            $requestData->addressInformation->shipping_address->altFirstName : '';
        $altLastName = isset($requestData->addressInformation->shipping_address->altLastName) ?
            $requestData->addressInformation->shipping_address->altLastName : '';
        $altEmail = isset($requestData->addressInformation->shipping_address->altEmail) ?
            $requestData->addressInformation->shipping_address->altEmail : '';
        $altPhoneNumber = isset($requestData->addressInformation->shipping_address->altPhoneNumber) ?
            $requestData->addressInformation->shipping_address->altPhoneNumber : '';
        $altPhoneNumberext = isset($requestData->addressInformation->shipping_address->altPhoneNumberext) ?
            $requestData->addressInformation->shipping_address->altPhoneNumberext : '';
        if ($isAlternate) {
            $quote->setData('customer_firstname', $altFirstName);
            $quote->setData('customer_lastname', $altLastName);
            $quote->setData('customer_email', $altEmail);
            $quote->setData('customer_telephone', $altPhoneNumber);
            $quote->setData('customer_PhoneNumber_ext', $altPhoneNumberext);
            $quote->setData(self::EXT_NO, $altPhoneNumberext);
            if (!$isCommercialCustomer || $isSdeCustomer) {
                $quote->getShippingAddress()->setSameAsBilling(false);
            }
        }
    }

    /**
     * Set Shipping Information for Pickup Quote
     */
    public function setShippingInformation($quote, $requestData) : void
    {
        if (isset($requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS])) {
            $shippingAddress = $requestData[self::SHIPPING_INFORMATION]
                            [self::ADDRESS_INFORMATION][self::SHIPPING_ADDRESS];
            $quote->getShippingAddress()->setStreet($shippingAddress[self::STREET][0]);
            $quote->getShippingAddress()->setCity($shippingAddress["city"]);
            $quote->getShippingAddress()->setCountryId($shippingAddress[self::COUNTRY_ID]);
            $quote->getShippingAddress()->setPostcode($shippingAddress[self::POSTCODE]);
            $quote->getShippingAddress()->setRegion($shippingAddress[self::REGION]);
            $quote->getShippingAddress()->setRegionId($shippingAddress[self::REGION_ID]);
            $quote->getShippingAddress()->setCollectShippingRates(true);

            if (isset($requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION][self::BILLING_ADDRESS])) {
                $billingAddress = $requestData[self::SHIPPING_INFORMATION]
                                [self::ADDRESS_INFORMATION][self::BILLING_ADDRESS];
                $quote->getBillingAddress()->setStreet($billingAddress[self::STREET][0]);
                $quote->getBillingAddress()->setCity($billingAddress["city"]);
                $quote->getBillingAddress()->setCountryId($billingAddress[self::COUNTRY_ID]);
                $quote->getBillingAddress()->setPostcode($billingAddress[self::POSTCODE]);
                $quote->getBillingAddress()->setRegion($billingAddress[self::REGION]);
                $quote->getBillingAddress()->setRegionId($billingAddress[self::REGION_ID]);
                $quote->getBillingAddress()->setCollectShippingRates(true);
            } else {
                $quote->getBillingAddress()->setStreet($shippingAddress[self::STREET][0]);
                $quote->getBillingAddress()->setCity($shippingAddress["city"]);
                $quote->getBillingAddress()->setCountryId($shippingAddress[self::COUNTRY_ID]);
                $quote->getBillingAddress()->setPostcode($shippingAddress[self::POSTCODE]);
                $quote->getBillingAddress()->setRegion($shippingAddress[self::REGION]);
                $quote->getBillingAddress()->setRegionId($shippingAddress[self::REGION_ID]);
                $quote->getBillingAddress()->setCollectShippingRates(true);
            }
        }
    }

     /**
      * Manage coupon code reset in case of alert from Rate API.
      *
      * @param rateResponse $rateResponse
      * @param Quote $quote
      */
    public function resetPromoCode($rateResponse, $quote)
    {
        $alertMessage = null;
        $alertCode = $rateResponse['alerts'][0]['code'];
        if ($alertCode == "COUPONS.CODE.INVALID") {
            $alertMessage = 'Promo code invalid. Please try again.';
            $quote->setCouponCode();
        } elseif ($alertCode == "MINIMUM.PURCHASE.REQUIRED") {
            $alertMessage = 'Minimum purchase amount not met.';
            $quote->setCouponCode();
        } elseif ($alertCode == "INVALID.PRODUCT.CODE") {
            $alertMessage = $rateResponse['alerts'][0]['message'];
            $quote->setCouponCode();
        } elseif ($alertCode == "COUPONS.CODE.EXPIRED") {
            $alertMessage = 'Promo code has expired. Please try again.';
            $quote->setCouponCode();
        } elseif ($alertCode == "COUPONS.CODE.REDEEMED") {
            $alertMessage = 'Promo code has already been redeemed.';
            $quote->setCouponCode();
        }

        return $alertMessage;
    }

    /**
     * Validate contact information in request data
     *
     * @param  Object $requestData
     * @return bool
     */
    public function isValidContactInformation($requestData)
    {
        $validated = false;
        if (!empty($requestData)) {
            $requestData = json_decode($requestData, true);
            if (!empty($requestData)) {
                $contactInfo = $requestData['contactInformation'] ?? null;
                if ($contactInfo) {
                    $contactFirstName = $contactInfo['contact_fname'] ?? null;
                    $contactLastName = $contactInfo['contact_lname'] ?? null;
                    $contactEmail = $contactInfo['contact_email'] ?? null;
                    $contactNumber = $contactInfo['contact_number'] ?? null;

                    if (!empty($contactFirstName) &&
                    !empty($contactLastName) && !empty($contactEmail) && !empty($contactNumber)) {
                        $validated = true;
                    }
                }
            }
        }
        return $validated;
    }

    /**
     * Validate shipping information in request data
     *
     * D-85181 | Missing shipping options
     *
     * @param  Object $requestData
     * @return bool
     */
    public function isValidateShippingDetailQuoteRequest($requestData)
    {
        $validated = false;
        if (!empty($requestData)) {
            $requestData = json_decode($requestData, true);

            $addressInfo = $requestData[self::ADDRESS_INFORMATION] ?? null;
            if ($addressInfo) {
                $validated = $this->isValidateShippingData($addressInfo);
                
                $pickup = $addressInfo['pickup'] ?? null;
                if (empty($pickup)) {
                    $shipFirstName = $addressInfo[self::SHIPPING_ADDRESS][self::FIRST_NAME] ?? null;
                    $shipLastName = $addressInfo[self::SHIPPING_ADDRESS][self::LAST_NAME] ?? null;
                    if (!empty($shipFirstName) && !empty($shipLastName)) {
                        $validated = true;
                    } else {
                        $validated = false;
                    }
                }
            }
        }

        return $validated;
    }

    /**
     * Validate shipping methods details availability in request
     *
     * @param  Array   $addressInfo
     * @return Boolean $validated
     */
    public function isValidateShippingData($addressInfo)
    {
        $validated = true;

        $shipMethodCode = $addressInfo['shipping_method_code'] ?? null;
        $shipCarrierCode = $addressInfo['shipping_carrier_code'] ?? null;
        $shippingDetailCarrierCode = $addressInfo[self::SHIPPING_DETAIL]['carrier_code'] ?? null;
        $shippingDetailMethodCode = $addressInfo[self::SHIPPING_DETAIL]['method_code'] ?? null;
        $shippingDetailCarrierTitle = $addressInfo[self::SHIPPING_DETAIL][self::CARRIER_TITLE] ?? null;

        if (empty($shipMethodCode) || empty($shipCarrierCode) || empty($shippingDetailCarrierCode)) {
            $validated = false;
        }
        if (empty($shippingDetailMethodCode) || empty($shippingDetailCarrierTitle)) {
            $validated = false;
        }

        return $validated;
    }

    /**
     * Set Shipping Info
     * @param object $quote
     * @param array $requestData
     */
    public function setShippingInfo($quote, $requestData)
    {
        $shippingMethodCode = isset($requestData[self::SHIPPING_INFORMATION]
                [self::ADDRESS_INFORMATION]['shipping_method_code']) ?
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['shipping_method_code'] : '';
        $shippingCarrierCode = isset($requestData[self::SHIPPING_INFORMATION]
            [self::ADDRESS_INFORMATION]['shipping_carrier_code']) ?
                $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['shipping_carrier_code'] : '';
        $shipMethodTitle = isset($requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['method_title']) ?
            $requestData[self::SHIPPING_INFORMATION][self::ADDRESS_INFORMATION]['method_title'] : '';
        $shipInfo = [
            'shippingMethodCode'  => $shippingMethodCode,
            'shippingCarrierCode' => $shippingCarrierCode,
            'shipMethodTitle'     => $shipMethodTitle
        ];

        $quote->setData('ext_shipping_info', json_encode($shipInfo));
    }

    /**
     * Check if negotiable quote existing for quote
     *
     * @param int $quoteId
     * @return int|boolean|null
     */
    public function checkNegotiableQuoteExistingForQuote($quoteId)
    {
        return $this->adminConfigHelper->isNegotiableQuoteExistingForQuote($quoteId);
    }

    /**
     * Update quote log in negotiable quote history
     *
     * @param int $quoteId
     * @param string $quoteStatus
     * @return void
     */
    public function updateEproQuoteStatusByKey($quoteId, $quoteStatus)
    {
        return $this->adminConfigHelper->updateFinalQuoteStatus($quoteId, $quoteStatus);
    }

    /**
     * Update Epro Negotiable Quote when upload to quote submit
     *
     * @param object $quote
     * @return void
     */
    public function updateEproNegotiableQuote($quote)
    {
        return $this->adminConfigHelper->updateEproNegotiableQuote($quote);
    }
    /**
     * Set converted at date for quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    public function setConvertedAtDateForQuote($quote): void {
        $currentDate = $this->timezoneInterface->date('', null, false, false)->format('Y-m-d H:i:s');
        $quote->setConvertedAt($currentDate);
    }

    public function checkIfuploadToQuote($quote, $uploadToQuote, $negotiableQuote){
        if ($uploadToQuote) {
            $negotiableQuote->setQuoteName("Upload To Quote Creation")
            ->setQuoteMgntLocationCode($quote->getQuoteMgntLocationCode());
            if($this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D210584_FIX)){
                $this->setConvertedAtDateForQuote($quote);
            }
        }   
    }

    public function checkIffuseBidding($quote, $fuseBidding, $negotiableQuote){
        if ($fuseBidding) {
            $negotiableQuote->setQuoteName("Fuse bidding Quote Creation")
            ->setQuoteMgntLocationCode($quote->getQuoteMgntLocationCode());
            if($this->toggleConfig->getToggleConfigValue(self::MAZEGEEKS_D210584_FIX)){
                $this->setConvertedAtDateForQuote($quote);
            }
        }
    }
}
