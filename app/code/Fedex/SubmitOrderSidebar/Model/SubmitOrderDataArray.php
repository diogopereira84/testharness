<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Fedex\ComputerRental\Model\CRdataModel;
use Fedex\GraphQl\Helper\Data;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Model\Organization;
use Fedex\ProductBundle\Api\ConfigInterface;
use Fedex\SubmitOrderSidebar\Api\BillingFieldBuilderInterface;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\MarketplaceProduct\Api\ShopManagementInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\DataObjectFactory;
use Fedex\InBranch\Model\InBranchValidation;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\MarketplaceCheckout\Helper\Data as MarketplaceCheckoutHelper;

class SubmitOrderDataArray
{
    public const CURRENCY = "USD";
    public const CURRENCY_TEXT = "currency";
    public const PAYMENT_TYPE = "paymentType";
    public const ACCOUNT_TEXT = "account";
    public const ACCOUNT_NUMBER = "accountNumber";
    public const ACCOUNT = "ACCOUNT";
    public const RESPONSIBLE_PARTY = "SENDER";
    public const CREDIT_CARD = "CREDIT_CARD";
    public const STREET_LINES = "streetLines";
    public const STATE_OR_PROVINCE_CODE = "stateOrProvinceCode";
    public const POSTALCODE = "postalCode";
    public const COUNTRYCODE = "countryCode";
    public const ADDRESS_CLASSIFICATION = "addressClassification";
    public const PONUMBER = "poNumber";
    public const BILLING_FIELDS = 'billingFields';
    public const RATE_QUOTE_ACTIONS = ['SAVE', 'COMMIT', 'SAVE_COMMIT'];
    public const CONTACT = "contact";
    public const CONTACTID = "contactId";
    public const PERSONNAME = "personName";
    public const FIRSTNAME = "firstName";
    public const LASTNAME = "lastName";
    public const COMPANY = "company";
    public const EMAILDETAIL = "emailDetail";
    public const REQUESTEDAMOUNT = "requestedAmount";
    public const FEDEXACCOUNTNUMBER = "fedExAccountNumber";
    public const EMAILADDRESS = "emailAddress";
    public const PHONENUMBERDETAILS = "phoneNumberDetails";
    public const PHONENUMBER = "phoneNumber";
    public const NUMBER = "number";
    public const FUSE_CLIENT = "FUSE";
    public const TIGER_ESSENDANT_TOGGLE = 'tiger_e458381_essendant';

    /**
     * @param Data $graphQlHelper
     * @param RequestQueryValidator $requestQueryValidator
     * @param BillingFieldBuilderInterface $billingFieldBuilder
     * @param QuoteHelper $quoteHelper
     * @param ShopManagementInterface $shopManagement
     * @param CollectionFactory $collectionFactory
     * @param DataObjectFactory $dataObjectFactory
     * @param InBranchValidation $inBranchValidation
     * @param Organization $organization
     * @param ToggleConfig $toggleConfig
     * @param OrderApprovalViewModel $orderApprovalViewModel
     * @param CRdataModel $crData
     * @param MarketplaceCheckoutHelper $marketplaceCheckoutHelper
     */
    public function __construct(
        private Data $graphQlHelper,
        private RequestQueryValidator $requestQueryValidator,
        private readonly BillingFieldBuilderInterface $billingFieldBuilder,
        Private QuoteHelper $quoteHelper,
        Private ShopManagementInterface $shopManagement,
        Private CollectionFactory $collectionFactory,
        private DataObjectFactory $dataObjectFactory,
        private InBranchValidation $inBranchValidation,
        private Organization $organization,
        protected ToggleConfig $toggleConfig,
        protected OrderApprovalViewModel $orderApprovalViewModel,
        private CRdataModel $crData,
        private MarketplaceCheckoutHelper $marketplaceCheckoutHelper,
        private readonly ConfigInterface $productBundleConfig
    )
    {
    }

    /**
     * Get Transaction Order Details
     *
     * @param $transactionDataObject
     * @return array[]
     */
    public function getTransactionOrderDetails($transactionDataObject)
    {
        $newFijtsuToggle = $this->toggleConfig->getToggleConfigValue('new_fujitsu_receipt_approach');
        $date = $transactionDataObject->getDate();
        $fjmpRateQuoteId = $transactionDataObject->getFjmpRateQuoteId();
        $fName = $transactionDataObject->getFname();
        $lName = $transactionDataObject->getLname();
        $companyName = $transactionDataObject->getCompanyName();
        $email = $transactionDataObject->getEmail();
        $phNumber = $transactionDataObject->getPhNumber();
        $extension = $transactionDataObject->getExtension();
        $toasMappingredesignToggle = $this->toggleConfig->getToggleConfigValue('explorers_toas_mapping_redesign');

        $reciptType = 'NONE';
        $receiptFormat = 'STANDARD';
        if ($newFijtsuToggle) {
            $reciptType = 'EMAIL';
            $receiptFormat = 'INVOICE_EIGHT_BY_ELEVEN';
        }
        if ($toasMappingredesignToggle) {
            $gtnNumber = $transactionDataObject->getOrderNumber();
            $transactionHeader = [
                'requestDateTime' => $date,
                'rateQuoteId' => $fjmpRateQuoteId,
                'type' => "SALE",
                'orderReferences' => [
                    [
                        'name' => "MAGENTO",
                        'value' => $gtnNumber
                    ]
                ]
            ];
        } else {
            $transactionHeader = [
               'requestDateTime' => $date,
                'rateQuoteId' => $fjmpRateQuoteId,
                'type' => "SALE"
            ];
        }

        return [
            'checkoutRequest' => [
                'transactionHeader' => $transactionHeader,
                'transactionReceiptDetails' => [
                    'receiptType' => $reciptType,
                    'receiptFormat' => $receiptFormat,
                ],
                self::CONTACT => [
                    self::CONTACTID => null,
                    self::PERSONNAME => [
                        self::FIRSTNAME => $fName,
                        self::LASTNAME => $lName,
                    ],
                    self::COMPANY => [
                        'name' => $companyName,
                    ],
                    self::EMAILDETAIL => [
                        self::EMAILADDRESS => $email,
                    ],
                    self::PHONENUMBERDETAILS => [
                        0 => [
                            self::PHONENUMBER => [
                                self::NUMBER => $phNumber,
                                'extension' => $extension,
                            ],
                            'usage' => 'PRIMARY',
                        ],
                    ],
                ],
                'tenders' => [],
            ],
        ];
    }

    /**
     * Get Order Details
     *
     * @param object $dataObject
     * @param object $quote
     * @param bool $isB2bEnabled
     * @param bool $isOrderApproval
     * @return array|array[]
     */
    public function getOrderDetails(
        $dataObject,
        $quote,
        $isB2bEnabled = false,
        $isOrderApproval = false
    )
    {
        $pickupStore = $dataObject->getPickStore();
        $action = static::RATE_QUOTE_ACTIONS[2];

        //B2b rder approval flow
        if ($isB2bEnabled && !$isOrderApproval) {
            $action = static::RATE_QUOTE_ACTIONS[0];
        }
        //B2b rder approval flow

        $rateQuoteId = $dataObject->getRateQuoteId() ?? null;

        if (!$pickupStore) {
            $data = $this->prepareShippingRateQuoteRequestData(
                $rateQuoteId,
                $action,
                $dataObject,
                $quote,
                $isOrderApproval
            );
        } else {
            $data = $this->preparePickupRateQuoteRequestData(
                $rateQuoteId,
                $action,
                $dataObject,
                $quote,
                $isOrderApproval
            );
        }

        return $data;
    }

    /**
     * Prepare Shipping Flow Rate Quote Request Data
     *
     * @param string $rateQuoteId
     * @param string $action
     * @param object $dataObject
     * @param object $quote
     * @param boolean $isOrderApproval
     * @return array[]
     */
    public function prepareShippingRateQuoteRequestData(
        $rateQuoteId,
        $action,
        $dataObject,
        $quote,
        $isOrderApproval = false
    )
    {
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $fedExAccountNumber = $dataObject->getFedExAccountNumber();
        $lteIdentifier = $dataObject->getLteIdentifier();
        $orderNumber = $dataObject->getOrderNumber();
        $siteName = $dataObject->getSiteName();
        $companySite = $dataObject->getCompanySite();
        $userReferences = $dataObject->getUserReferences();
        $fName = $dataObject->getFname();
        $lName = $dataObject->getLname();
        $email = $dataObject->getEmail();
        $telephone = $dataObject->getTelephone();
        $extension = $dataObject->getExtension();
        $recipientFname = $dataObject->getRecipientFname();
        $recipientLname = $dataObject->getRecipientLname();
        $recipientEmail = $dataObject->getRecipientEmail();
        $companyName = null;
        if($isCompanyNameToggleEnabled) {
            $companyName = $dataObject->getCompany();
        }
        $recipientTelephone = $dataObject->getRecipientTelephone();
        $recipientExt = $dataObject->getRecipientExtension();
        $webhookUrl = $dataObject->getWebhookUrl();
        $product = $dataObject->getProductData();
        $shipmentId = $dataObject->getShipmentId();
        $productAssociations = $dataObject->getProductAssociations();
        $promoCodeArray = $dataObject->getPromoCodeArray();
        $poReferenceId = $dataObject->getPoReferenceId();
        $streetAddress = $dataObject->getStreetAddress();
        $city = $dataObject->getCity();
        $shipperRegion = $dataObject->getShipperRegion();
        $zipcode = $dataObject->getZipCode();
        $addressClassification = $dataObject->getAddressClassification();
        $shipMethod = $dataObject->getShipMethod();
        $fedexShipAccountNumber = $dataObject->getFedexShipAccountNumber();
        $locationId = $dataObject->getLocationId();
        $contactId = null;
        if ($this->requestQueryValidator->isGraphQl()) {
            $contactId = $dataObject->getContactId();
        }

        $isAlternatePerson = $quote->getIsAlternate() ? true : false;
        $isEproQuote = $quote->getIsEproQuote() ? true : false;
        $explorersD193256FixToggle = $this->toggleConfig
                ->getToggleConfigValue('explorers_d_193256_fix');
        if ($explorersD193256FixToggle && $isAlternatePerson && $isEproQuote) {
            $fName = $quote->getData('customer_firstname');
            $lName = $quote->getData('customer_lastname');
            $email = $quote->getData('customer_email');
            $telephone = $quote->getData('customer_telephone');
            $extension = $quote->getData('ext_no');
        }

        $recipientDataObject = $this->prepareReceipientDataObject(
            $quote,
            $shipmentId,
            $recipientFname,
            $fName,
            $recipientLname,
            $lName,
            $recipientEmail,
            $email,
            $companyName,
            $recipientTelephone,
            $telephone,
            $recipientExt,
            $extension,
            $streetAddress,
            $city,
            $shipperRegion,
            $zipcode,
            $addressClassification,
            $shipMethod,
            $fedexShipAccountNumber,
            $poReferenceId,
            $productAssociations,
            $contactId,
            $locationId,
            ""
        );
        $quoteShip = $quote->getShippingAddress();
        $recipients = $this->getReciepientsData($recipientDataObject,false,$isOrderApproval);
        $fedexLocationId = $this->getFedexLocationId();
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        if ($isCompanyNameToggleEnabled) {
            if ($this->requestQueryValidator->isGraphQl()) {
                $organizationName = $this->organization->getOrganization($quoteShip->getCompany() ?? '');
            } else {
                $organizationName = $companyName ?? 'FXO';
            }
        } else {
            $organizationName = $this->organization->getOrganization($quoteShip->getCompany() ?? '');
        }
        return [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => $dataObject->getSourceRetailLocationId(),
                'previousQuoteId' => $rateQuoteId,
                'action' => $action,
                'retailPrintOrder' => [
                    self::FEDEXACCOUNTNUMBER => $fedExAccountNumber,
                    'lteIdentifier' => $lteIdentifier,
                    'origin' => [
                        'orderNumber'    => $orderNumber,
                        'orderClient'    => $dataObject->getOrderClient(),
                        'site'           => $siteName,
                        'siteName'       => $companySite,
                        'userReferences' => $userReferences,
                        'fedExLocationId'=>$fedexLocationId
                    ],
                    'orderContact' => [
                        self::CONTACT => [
                            self::CONTACTID => $contactId,
                            self::PERSONNAME => [
                                self::FIRSTNAME => $fName,
                                self::LASTNAME => $lName,
                            ],
                            self::COMPANY => [
                                'name' => $organizationName
                            ],
                            self::EMAILDETAIL => [
                                self::EMAILADDRESS => $email,
                            ],
                            self::PHONENUMBERDETAILS => [
                                0 => [
                                    self::PHONENUMBER => [
                                        self::NUMBER => trim((string)$telephone),
                                        'extension' => !empty($extension) ? $extension : null,
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => $webhookUrl,
                            'auth' => null,
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => $product,
                    'recipients' => $recipients,
                    'notes' => $dataObject->getNotes(),
                ],
                'coupons' => !empty($promoCodeArray['code']) ? [$promoCodeArray] : null,
                'teamMemberId' => $this->graphQlHelper->getJwtParamByKey('employeeNumber'),
            ],
        ];
    }

     /**
     * Prepare Pickup Flow Rate Quote Request Data
     *
     * @param string $rateQuoteId
     * @param string $action
     * @param object $dataObject
     * @param object $quote
     * @param boolean $isOrderApproval
     * @return array[]
     */
    public function preparePickupRateQuoteRequestData(
        $rateQuoteId,
        $action,
        $dataObject,
        $quote,
        $isOrderApproval = false
    )
    {
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $fedExAccountNumber = $dataObject->getFedExAccountNumber();
        $lteIdentifier = $dataObject->getLteIdentifier();
        $orderNumber = $dataObject->getOrderNumber();
        $companySite = $dataObject->getCompanySite();
        $siteName = $dataObject->getSiteName();
        $userReferences = $dataObject->getUserReferences();
        $fName = $dataObject->getFname();
        $lName = $dataObject->getLname();
        $email = $dataObject->getEmail();
        $companyName = null;
        if($isCompanyNameToggleEnabled) {
            $companyName = $dataObject->getCompany();
        }
        $telephone = $dataObject->getTelephone();
        $extension = $dataObject->getExtension();
        $recipientFname = $dataObject->getRecipientFname();
        $recipientLname = $dataObject->getRecipientLname();
        $recipientEmail = $dataObject->getRecipientEmail();
        $recipientTelephone = $dataObject->getRecipientTelephone();
        $recipientExt = $dataObject->getRecipientExtension();
        $webhookUrl = $dataObject->getWebhookUrl();
        $product = $dataObject->getProductData();
        $shipmentId = $dataObject->getShipmentId();
        $productAssociations = $dataObject->getProductAssociations();
        $promoCodeArray = $dataObject->getPromoCodeArray();
        $locationId = $dataObject->getLocationId();
        if ($isOrderApproval) {
            $orderObj = $this->orderApprovalViewModel->getOrder($orderNumber);
            $locationId = !empty($orderObj) ? $orderObj->getShippingDescription() : "";
        }
        $requestedPickupLocalTime = $dataObject->getRequestedPickupLocalTime();
        $contactId = null;
        if ($this->requestQueryValidator->isGraphQl()) {
            $contactId = $dataObject->getContactId();
        }
        $isAlternatePickupPerson = $quote->getIsAlternatePickup() ? true : false;
        $isEproQuote = $quote->getIsEproQuote() ? true : false;
        $explorersD197503FixToggle = $this->toggleConfig
                ->getToggleConfigValue('explorers_d_197503_fix');
        if ($explorersD197503FixToggle && $isAlternatePickupPerson && $isEproQuote) {
            $fName = $quote->getData('customer_firstname');
            $lName = $quote->getData('customer_lastname');
            $email = $quote->getData('customer_email');
            $telephone = $quote->getData('customer_telephone');
            $extension = $quote->getData('ext_no');
        }

        $recipientDataObject = $this->prepareReceipientDataObject(
            $quote,
            $shipmentId,
            $recipientFname,
            $fName,
            $recipientLname,
            $lName,
            $recipientEmail,
            $email,
            $companyName,
            $recipientTelephone,
            $telephone,
            $recipientExt,
            $extension,
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            "",
            $productAssociations,
            $contactId,
            $locationId,
            $requestedPickupLocalTime
        );

        $quoteShip = $quote->getShippingAddress();
        $recipients = $this->getReciepientsData($recipientDataObject,true);
        $fedexLocationId = $this->getFedexLocationId();
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        if ($isCompanyNameToggleEnabled) {
            if ($this->requestQueryValidator->isGraphQl()) {
                $organizationName = $this->organization->getOrganization($quoteShip->getCompany() ?? '');
            } else {
                $organizationName = $companyName ?? 'FXO';
            }
        } else {
            $organizationName = $this->organization->getOrganization($quoteShip->getCompany() ?? '');
        }
        return [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => $dataObject->getSourceRetailLocationId(),
                'previousQuoteId' => $rateQuoteId,
                'action' => $action,
                'retailPrintOrder' => [
                    self::FEDEXACCOUNTNUMBER => $fedExAccountNumber,
                    'lteIdentifier' => $lteIdentifier,
                    'origin' => [
                        'orderNumber' => $orderNumber,
                        'orderClient' => $dataObject->getOrderClient(),
                        'site'           => $siteName,
                        'siteName'       => $companySite,
                        'userReferences' => $userReferences,
                        'fedExLocationId'=>$fedexLocationId
                    ],
                    'orderContact' => [
                        self::CONTACT => [
                            self::CONTACTID => $contactId,
                            self::PERSONNAME => [
                                self::FIRSTNAME => $fName,
                                self::LASTNAME => $lName,
                            ],
                            self::COMPANY => [
                                'name' => $organizationName
                            ],
                            self::EMAILDETAIL => [
                                self::EMAILADDRESS => $email,
                            ],
                            self::PHONENUMBERDETAILS => [
                                0 => [
                                    self::PHONENUMBER => [
                                        self::NUMBER => trim((string)$telephone),
                                        'extension' => $extension,
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => $webhookUrl,
                            'auth' => null,
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => $product,
                    'recipients' => $recipients,
                    'notes' => $dataObject->getNotes(),
                ],
                'coupons' => !empty($promoCodeArray['code']) ? [$promoCodeArray] : null,
                'teamMemberId' => $this->graphQlHelper->getJwtParamByKey('employeeNumber'),
            ],
        ];
    }

    /**
     * Get Checkout Request Tender Data
     *
     * @param $dataObject
     * @return array[]
     */
    public function getCheckoutRequestTenderData($dataObject, $quote)
    {
        $isOrderApproval = $dataObject->getIsB2bApproval() ?? false;
        $numDiscountPrice = $dataObject->getNumDiscountPrice();
        $shippingAccount = $dataObject->getShippingAccount();
        $requestedAmount = $dataObject->getRequestedAmount();
        $numTotal = $dataObject->getNumTotal();
        $accNo = $dataObject->getAccNo();
        $condition = $dataObject->getCondition();
        $paymentMethod = $dataObject->getPaymentMethod();
        $collection = $this->billingFieldBuilder->build($quote, $isOrderApproval);
        $poReferenceId = $dataObject->getPoReferenceId();
        if ($paymentMethod == "cc") {
            return $this->getCreditCardRequestBuilder($dataObject, $collection);
        } else {
            if (!$this->isD200699Enabled() && $collection->hasPoNumber()) {
                $poReferenceId = $collection->getPoNumer();
                $collection->removePoReferenceId();
            }

            if ($condition) {
                $arr = [
                    0 => [
                        'id' => "1",
                        self::CURRENCY_TEXT => self::CURRENCY,
                        self::PAYMENT_TYPE => self::ACCOUNT,
                        self::REQUESTEDAMOUNT => $numDiscountPrice,
                        self::ACCOUNT_TEXT => [
                            self::ACCOUNT_NUMBER => $shippingAccount,
                            'responsibleParty' => "SENDER",
                        ],
                    ],
                    1 => [
                        'id' => "2",
                        self::CURRENCY_TEXT => self::CURRENCY,
                        self::PAYMENT_TYPE => self::ACCOUNT,
                        self::REQUESTEDAMOUNT => $requestedAmount,
                        self::PONUMBER => $poReferenceId,
                        self::ACCOUNT_TEXT => [
                            self::ACCOUNT_NUMBER => $accNo,
                        ],
                    ],
                ];
                if ($collection->toArrayApi()) {
                    $arr[1][self::BILLING_FIELDS] = $collection->toArrayApi();
                }
                return $arr;
            } else {
                $arr = [
                    0 => [
                        'id' => "1",
                        self::CURRENCY_TEXT => self::CURRENCY,
                        self::PAYMENT_TYPE => self::ACCOUNT,
                        self::REQUESTEDAMOUNT => $numTotal,
                        self::PONUMBER => $poReferenceId,
                        self::ACCOUNT_TEXT => [
                            self::ACCOUNT_NUMBER => $accNo,
                        ],
                    ],
                ];
                if ($collection->toArrayApi()) {
                    $arr[0][self::BILLING_FIELDS] = $collection->toArrayApi();
                }
                return $arr;
            }
        }
    }

    /**
     * Get Credit Card Request Builder
     *
     * @param $dataObject
     * @return array[]
     */
    public function getCreditCardRequestBuilder($dataObject, $collection)
    {
        $condition = $dataObject->getCondition();
        $numDiscountPrice = $dataObject->getNumDiscountPrice();
        $shippingAccount = $dataObject->getShippingAccount();
        $requestedAmount = $dataObject->getRequestedAmount();
        $encCCData = $dataObject->getEncCCData();
        $ccToken = $dataObject->getCcToken();
        $nameOnCard = $dataObject->getNameOnCard();
        $streetAddress = $dataObject->getStreetAddress();
        $city = $dataObject->getCity();
        $shipperRegion = $dataObject->getShipperRegion();
        $stateCode = $dataObject->getStateCode();
        $zipcode = $dataObject->getZipCode();
        $addressClassification = $dataObject->getAddressClassification();
        $expirationMonth = $dataObject->getExpirationMonth();
        $expirationYear = $dataObject->getExpirationYear();
        $poReferenceId = $dataObject->getPoReferenceId();
        $numTotal = $dataObject->getNumTotal();
        $state = $dataObject->getState();

        if (!$this->isD200699Enabled() && $collection->hasPoNumber()) {
            $poReferenceId = $collection->getPoNumer();
            $collection->removePoReferenceId();
        }

        if ($condition) {
            $arr = [
                0 => [
                    'id' => "1",
                    self::CURRENCY_TEXT => self::CURRENCY,
                    self::PAYMENT_TYPE => self::ACCOUNT,
                    self::REQUESTEDAMOUNT => $numDiscountPrice,
                    self::ACCOUNT_TEXT => [
                        self::ACCOUNT_NUMBER => $shippingAccount,
                        'responsibleParty' => self::RESPONSIBLE_PARTY,
                    ],
                ],
                1 => [
                    'id' => "2",
                    self::CURRENCY_TEXT => self::CURRENCY,
                    self::PAYMENT_TYPE => self::CREDIT_CARD,
                    self::REQUESTEDAMOUNT => $requestedAmount,
                    'creditCard' => [
                        'encryptedCreditCard' => $encCCData,
                        'token' => $ccToken,
                        'cardHolderName' => $nameOnCard,
                        'billingAddress' => [
                            self::STREET_LINES => $streetAddress,
                            'city' => $city,
                            self::STATE_OR_PROVINCE_CODE => isset($shipperRegion)
                            ? $shipperRegion->getCode() : $stateCode,
                            self::POSTALCODE => $zipcode,
                            self::COUNTRYCODE => 'US',
                            self::ADDRESS_CLASSIFICATION => $addressClassification,
                        ],
                        'expirationMonth' => $expirationMonth,
                        'expirationYear' => $expirationYear,
                    ],
                    self::PONUMBER => $poReferenceId,
                ],
            ];

            if ($collection->toArrayApi()) {
                $arr[1][self::BILLING_FIELDS] = $collection->toArrayApi();
            }
            return $arr;
        } else {
            $arr = [
                0 => [
                    'id' => "1",
                    self::CURRENCY_TEXT => self::CURRENCY,
                    self::PAYMENT_TYPE => self::CREDIT_CARD,
                    self::REQUESTEDAMOUNT => $numTotal,
                    'creditCard' => [
                        'encryptedCreditCard' => $encCCData,
                        'token' => $ccToken,
                        'cardHolderName' => $nameOnCard,
                        'billingAddress' => [
                            self::STREET_LINES => $streetAddress,
                            'city' => $city,
                            self::STATE_OR_PROVINCE_CODE => isset($shipperRegion)
                            ? $shipperRegion->getCode() : $state,
                            self::POSTALCODE => $zipcode,
                            self::COUNTRYCODE => 'US',
                            self::ADDRESS_CLASSIFICATION => $addressClassification,
                        ],
                        'expirationMonth' => $expirationMonth,
                        'expirationYear' => $expirationYear,
                    ],
                    self::PONUMBER => $poReferenceId,
                ],
            ];
            if ($collection->toArrayApi()) {
                $arr[0][self::BILLING_FIELDS] = $collection->toArrayApi();
            }
            return $arr;
        }
    }

    /**
     * Builds Receipient info for Rate Quote API
     * @param $isPickup
     * @param $recipientDataObject
     * @return mixed
     */
    public function getReceipientInfo($isPickup, $recipientDataObject, $isOrderApproval = false): array
    {
        //Inbranch Implementation
        $productionLocationId = null;
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $isEproStore = $this->inBranchValidation->isInBranchUser();
                if ($isEproStore) {
                    $productionLocationId = $this->inBranchValidation->getAllowedInBranchLocation();
                }
            $productionLocationFixToggle = $this->toggleConfig
                ->getToggleConfigValue('explorers_d188299_production_location_fix');
            if ($productionLocationFixToggle && empty($productionLocationId)) {
                $productionLocationId = !empty($recipientDataObject->getLocationId()) ? $recipientDataObject->getLocationId() : null;
            }
        }

        //Inbranch Implementation

        $receipients = [];
        // Mixed Cart or 1P Only Cart
        $quote = $recipientDataObject->getQuote();
        $shipperRegion = $recipientDataObject->getShipperRegion();

        // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
        $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            if (!$toggleD192068FixEnabled && $isOrderApproval) {
                $productionLocationId = null;
            } elseif($quote->getProductionLocationId()){
                $productionLocationId = $quote->getProductionLocationId();
            }
        }
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocationId = $quote->getShippingAddress()->getProductionLocation();
        }
        if($this->toggleConfig->getToggleConfigValue('tiger_d_220707_fix') && $productionLocationId == null){
            $productionLocationId = $quote->getProductionLocationId();
        }

        if (($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote))
            || (!$this->quoteHelper->isMiraklQuote($quote))) {

            if ($isPickup) {
                $receipients =
                    [
                        0 => [
                            'reference' => $recipientDataObject->getShipmentId(),
                            self::CONTACT => [
                                self::CONTACTID => $recipientDataObject->getContactId(),
                                self::PERSONNAME => [
                                    self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                    self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                                ],
                                self::COMPANY => [
                                    'name' => $this->organization
                                        ->getOrganization($quote->getShippingAddress()->getCompany() ?? '')
                                ],
                                self::EMAILDETAIL => [
                                    self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                                ],
                                self::PHONENUMBERDETAILS => [
                                    0 => [
                                        self::PHONENUMBER => [
                                            self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                                (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                            'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => $recipientDataObject->getLocationId(),
                                ],
                                'requestedPickupLocalTime' => $recipientDataObject->getRequestedPickupLocalTime(),
                            ],
                            'productAssociations' => $this->filterProductAssociation($recipientDataObject->getProductAssociations(), false)
                        ]
                    ];
            } else {
                $shipperRegionCode = is_object($shipperRegion) ? $shipperRegion->getData('code') : (string)$shipperRegion;
                $receipients = [
                    0 => [
                        'reference' => $recipientDataObject->getShipmentId(),
                        self::CONTACT => [
                            self::CONTACTID => $recipientDataObject->getContactId(),
                            self::PERSONNAME => [
                                self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                            ],
                            self::COMPANY => [
                                'name' => $this->organization
                                    ->getOrganization($quote->getShippingAddress()->getCompany() ?? '')
                            ],
                            self::EMAILDETAIL => [
                                self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                            ],
                            self::PHONENUMBERDETAILS => [
                                0 => [
                                    self::PHONENUMBER => [
                                        self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                            (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                        'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                        'shipmentDelivery' => [
                            'address' => [
                                self::STREET_LINES => $recipientDataObject->getStreetAddress(),
                                'city' => $recipientDataObject->getCity(),
                                self::STATE_OR_PROVINCE_CODE => !empty($shipperRegionCode) ? $shipperRegionCode : null,
                                self::POSTALCODE => $recipientDataObject->getZipcode(),
                                self::COUNTRYCODE => 'US',
                                self::ADDRESS_CLASSIFICATION => $recipientDataObject->getAddressClassification(),
                            ],
                            'holdUntilDate' => null,
                            'productionLocationId' => $productionLocationId,
                            'serviceType' => $recipientDataObject->getShipMethod(),
                            self::FEDEXACCOUNTNUMBER => $recipientDataObject->getFedexShipAccountNumber(),
                            'deliveryInstructions' => null,
                            self::PONUMBER => $recipientDataObject->getPoReferenceId(),
                        ],
                        'productAssociations' => $this->filterProductAssociation($recipientDataObject->getProductAssociations(), false)
                    ]
                ];
            }
        }

        // Mixed Cart or Marketplace Only Cart
        if ($this->quoteHelper->isMiraklQuote($quote)) {
            $shippingData = [];
            if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                if($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                    $items = $quote->getAllItems();
                } else {
                    $items = $quote->getAllVisibleItems();
                }
            }else{
                $items = $quote->getAllItems();
            }
            foreach ($items as $item) {
                if ($item->getMiraklOfferId()) {
                    $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                    if (isset($additionalData['mirakl_shipping_data'])) {
                        $shippingData = $additionalData['mirakl_shipping_data'];
                        $marketPlaceShippingMethodCode = $shippingData['method_code'];
                        $marketPlaceShippingMethodPrice = $shippingData['amount'];
                        $marketPlaceShippingDeliveryDate = $shippingData['deliveryDate'];

                        if ($isPickup) {
                            if (isset($shippingData['address'])) {
                                $recipientDataObject->setStreetAddress($shippingData['address']['street'] ?? '');
                                $recipientDataObject->setCity($shippingData['address']['city'] ?? '');

                                $shipperRegion = $this->dataObjectFactory->create();
                                $shipperRegion->setCode($shippingData['address']['region'] ?? '');
                                $recipientDataObject->setShipperRegion($shipperRegion);
                                $recipientDataObject->setZipcode($shippingData['address']['postcode'] ?? '');

                                $addressClassification = "HOME";
                                $company = $shippingData['address']['company'];
                                if (!empty($company)) {
                                    $addressClassification = "BUSINESS";
                                }

                                $recipientDataObject->setAddressClassification($addressClassification);
                                $recipientDataObject->setTelephone($shippingData['address']['telephone'] ?? '');
                                $recipientDataObject->setFName($shippingData['address']['firstname'] ?? '');
                                $recipientDataObject->setLname($shippingData['address']['lastname'] ?? '');

                                $emailAndExtInfo = $this->getCustomReceipientAttributes($shippingData['address']['customAttributes']);
                                $recipientDataObject->setEmail($emailAndExtInfo['email'] ?? '');
                                $recipientDataObject->setExtension($emailAndExtInfo['ext'] ?? '');

                                $recipientDataObject->setRecipientFname($shippingData['address']['altFirstName'] ?? '');
                                $recipientDataObject->setRecipientLname($shippingData['address']['altLastName'] ?? '');
                                $recipientDataObject->setRecipientTelephone($shippingData['address']['altPhoneNumber'] ?? '');
                                $recipientDataObject->setRecipientEmail($shippingData['address']['altEmail'] ?? '');
                                $recipientDataObject->setRecipientExt($shippingData['address']['altPhoneNumberext'] ?? '');
                            }
                        }
                        break;
                    }
                }
            }

            if (!empty($marketPlaceShippingMethodCode)) {
                $deliveryDate = date('Y-m-d', strtotime($marketPlaceShippingDeliveryDate));
                $quoteItem = $quote->getItemById((int)$shippingData["item_id"]);
                $shopData = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
                $shipperRegionCode = is_object($shipperRegion) ? $shipperRegion->getData('code') : (string)$shipperRegion;
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

                // TODO - Add exception logic when no delivery mapping found
                $shippingMethods = json_decode($shopArrayData['shipping_methods'], true);
                $shippingMethodCode = $shippingData['method_code'];
                $deliveryMethod = array_filter($shippingMethods, function ($var) use ($shippingMethodCode) {
                    return ($var['shipping_method_name'] == $shippingMethodCode);
                });
                $deliveryMethod = array_values($deliveryMethod);

                $receipients[] =
                    [
                        self::CONTACT => [
                            self::CONTACTID => null,
                            self::PERSONNAME => [
                                self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                            ],
                            self::COMPANY => [
                                'name' => 'FXO',
                            ],
                            self::EMAILDETAIL => [
                                self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                            ],
                            self::PHONENUMBERDETAILS => [
                                0 => [
                                    self::PHONENUMBER => [
                                        self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                            (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                        'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                        'reference' => $shippingData['reference_id'],
                        'externalDelivery' => [
                            'address' => [
                                self::STREET_LINES => $recipientDataObject->getStreetAddress(),
                                'city' => $recipientDataObject->getCity(),
                                self::STATE_OR_PROVINCE_CODE =>
                                    !empty($shipperRegionCode) ? $shipperRegionCode : null,
                                self::POSTALCODE => $recipientDataObject->getZipcode(),
                                self::COUNTRYCODE => 'US',
                                self::ADDRESS_CLASSIFICATION => $recipientDataObject->getAddressClassification(),
                            ],
                            'originAddress' => [
                                'streetLines' => [
                                    $shopArrayData["additional_info"]['contact_info']['street_1']
                                ],
                                'city' => $shopArrayData["additional_info"]['contact_info']['city'],
                                'stateOrProvinceCode' => $regionCode,
                                'postalCode' => $shopArrayData["additional_info"]['contact_info']['zip_code'],
                                'countryCode' => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0]),
                                'addressClassification' => $recipientDataObject->getAddressClassification()
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
                        'productAssociations' => $this->filterProductAssociation($recipientDataObject->getProductAssociations(), true)
                    ];
            }
        }
        return $receipients;
    }

    /**
     * @param $isPickup
     * @param $recipientDataObject
     * @param $isOrderApproval
     * @return array|array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getReceipientInfoUpdated($isPickup, $recipientDataObject, $isOrderApproval = false): array
    {
        //Inbranch Implementation
        $productionLocationId = null;
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $isEproStore = $this->inBranchValidation->isInBranchUser();
            if ($isEproStore) {
                $productionLocationId = $this->inBranchValidation->getAllowedInBranchLocation();
            }
            $productionLocationFixToggle = $this->toggleConfig
                ->getToggleConfigValue('explorers_d188299_production_location_fix');
            if ($productionLocationFixToggle && empty($productionLocationId)) {
                $productionLocationId = !empty($recipientDataObject->getLocationId()) ? $recipientDataObject->getLocationId() : null;
            }
        }
        //Inbranch Implementation

        $receipients = [];
        $itemData = $recipientDataObject->getProductAssociations();

        // Mixed Cart or 1P Only Cart
        $quote = $recipientDataObject->getQuote();
        $shipperRegion = $recipientDataObject->getShipperRegion();

        // D-192068 :: Commercial B2B Approval - Ship Orders don't route to customer selected production location
        $toggleD192068FixEnabled = $this->toggleConfig->getToggleConfigValue('explorers_D192068_fix');
        if (!$this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            if (!$toggleD192068FixEnabled && $isOrderApproval) {
                $productionLocationId = null;
            } elseif($quote->getProductionLocationId()){
                $productionLocationId = $quote->getProductionLocationId();
            }
        }
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_d_213795')) {
            $productionLocationId = $quote->getShippingAddress()->getProductionLocation();
        }
        if($this->toggleConfig->getToggleConfigValue('tiger_d_220707_fix') && $productionLocationId == null){
            $productionLocationId = $quote->getProductionLocationId();
        }
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');

        if (($this->quoteHelper->isMiraklQuote($quote) && !$this->quoteHelper->isFullMiraklQuote($quote))
            || (!$this->quoteHelper->isMiraklQuote($quote))) {

            if ($isPickup) {
                if ($isCompanyNameToggleEnabled) {
                    if ($this->requestQueryValidator->isGraphQl()) {
                        $organizationName = $this->organization->getOrganization($quote->getShippingAddress()->getCompany() ?? '');
                    } else {
                        $organizationName = $recipientDataObject->getCompanyName() ?? 'FXO';
                    }
                } else {
                    $organizationName = $this->organization->getOrganization($quote->getShippingAddress()->getCompany() ?? '');
                }
                $receipients =
                    [
                        0 => [
                            'reference' => $recipientDataObject->getShipmentId(),
                            self::CONTACT => [
                                self::CONTACTID => $recipientDataObject->getContactId(),
                                self::PERSONNAME => [
                                    self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                    self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                                ],
                                self::COMPANY => [
                                    'name' => $organizationName
                                ],
                                self::EMAILDETAIL => [
                                    self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                                ],
                                self::PHONENUMBERDETAILS => [
                                    0 => [
                                        self::PHONENUMBER => [
                                            self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                                (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                            'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => $recipientDataObject->getLocationId(),
                                ],
                                'requestedPickupLocalTime' => $recipientDataObject->getRequestedPickupLocalTime(),
                                'requestedDeliveryLocalTime' => $recipientDataObject->getRequestedPickupLocalTime(),
                            ],
                            'productAssociations' => $this->filterProductAssociation($itemData[0], false)
                        ]
                    ];
            } else {
                $shipperRegionCode = is_object($shipperRegion) ? $shipperRegion->getData('code') : (string)$shipperRegion;
                if ($isCompanyNameToggleEnabled) {
                    if ($this->requestQueryValidator->isGraphQl()) {
                        $organizationName = $this->organization->getOrganization($quote->getShippingAddress()->getCompany() ?? '');
                    } else {
                        $organizationName = $recipientDataObject->getCompanyName() ?? 'FXO';
                    }
                } else {
                    $organizationName = $this->organization->getOrganization($quote->getShippingAddress()->getCompany() ?? '');
                }
                $receipients = [
                    0 => [
                        'reference' => $recipientDataObject->getShipmentId(),
                        self::CONTACT => [
                            self::CONTACTID => $recipientDataObject->getContactId(),
                            self::PERSONNAME => [
                                self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                            ],
                            self::COMPANY => [
                                'name' => $organizationName
                            ],
                            self::EMAILDETAIL => [
                                self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                            ],
                            self::PHONENUMBERDETAILS => [
                                0 => [
                                    self::PHONENUMBER => [
                                        self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                            (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                        'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                        'shipmentDelivery' => [
                            'address' => [
                                self::STREET_LINES => $recipientDataObject->getStreetAddress(),
                                'city' => $recipientDataObject->getCity(),
                                self::STATE_OR_PROVINCE_CODE => !empty($shipperRegionCode) ? $shipperRegionCode : null,
                                self::POSTALCODE => $recipientDataObject->getZipcode(),
                                self::COUNTRYCODE => 'US',
                                self::ADDRESS_CLASSIFICATION => $recipientDataObject->getAddressClassification(),
                            ],
                            'holdUntilDate' => null,
                            'productionLocationId' => $productionLocationId,
                            'serviceType' => $recipientDataObject->getShipMethod(),
                            self::FEDEXACCOUNTNUMBER => $recipientDataObject->getFedexShipAccountNumber(),
                            'deliveryInstructions' => null,
                            self::PONUMBER => $recipientDataObject->getPoReferenceId(),
                        ],
                        'productAssociations' => $this->filterProductAssociation($itemData[0], false)
                    ]
                ];
            }
        }

        // Mixed Cart or Marketplace Only Cart
        $isShippingDataAvailable = false;
        if ($this->quoteHelper->isMiraklQuote($quote)) {
            $shippingData = [];
            if($this->marketplaceCheckoutHelper->isEssendantToggleEnabled()){
                if($this->productBundleConfig->isTigerE468338ToggleEnabled()) {
                    $items = $quote->getAllItems();
                } else {
                    $items = $quote->getAllVisibleItems();
                }
            }else{
                $items = $quote->getAllItems();
            }
            $sellers= $isShippingDataAvailableArray = [];

            foreach ($items as $item) {
                if (!$item->getMiraklOfferId()) {
                    continue;
                }
                $additionalData = json_decode($item->getAdditionalData() ?? '{}', true);
                $sellers[] = $item->getData('mirakl_shop_id');
                if (isset($additionalData['mirakl_shipping_data'])) {
                    $sellerId = $item->getData('mirakl_shop_id');
                    $shippingData[$sellerId] = $additionalData['mirakl_shipping_data'];
                }
            }

            foreach($sellers as $key=>$seller){
                $isShippingDataAvailableArray[] = (int)(array_key_exists($seller,$shippingData));
            }
            $isShippingDataAvailable = !(in_array(0,$isShippingDataAvailableArray));

            if (!empty($shippingData) && $isShippingDataAvailable) {
                foreach ($shippingData as $key => $value) {
                    $shippingDataBySeller = $shippingData[$key];
                    $deliveryDate = date('Y-m-d', strtotime($shippingDataBySeller['deliveryDate']));
                    $quoteItem = $quote->getItemById((int)$shippingDataBySeller["item_id"]);
                    $shopData = $this->shopManagement->getShopByProduct($quoteItem->getProduct());
                    $shipperRegionCode = is_object($shipperRegion) ? $shipperRegion->getData('code') : (string)$shipperRegion;
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
                    if ($isPickup && isset($shippingDataBySeller['address'])) {
                        $recipientDataObject->setStreetAddress($shippingDataBySeller['address']['street'] ?? '');
                        $recipientDataObject->setCity($shippingDataBySeller['address']['city'] ?? '');

                        $shipperRegion = $this->dataObjectFactory->create();
                        $shipperRegion->setCode($shippingDataBySeller['address']['region'] ?? '');
                        $recipientDataObject->setShipperRegion($shipperRegion);
                        $recipientDataObject->setZipcode($shippingDataBySeller['address']['postcode'] ?? '');

                        $addressClassification = "HOME";
                        $company = $shippingDataBySeller['address']['company'];
                        if (!empty($company)) {
                            $addressClassification = "BUSINESS";
                        }

                        $recipientDataObject->setAddressClassification($addressClassification);
                        $recipientDataObject->setTelephone($shippingDataBySeller['address']['telephone'] ?? '');
                        $recipientDataObject->setFName($shippingDataBySeller['address']['firstname'] ?? '');
                        $recipientDataObject->setLname($shippingDataBySeller['address']['lastname'] ?? '');

                        $emailAndExtInfo = $this->getCustomReceipientAttributes($shippingDataBySeller['address']['customAttributes']);
                        $recipientDataObject->setEmail($emailAndExtInfo['email'] ?? '');
                        $recipientDataObject->setExtension($emailAndExtInfo['ext'] ?? '');

                        $recipientDataObject->setRecipientFname($shippingDataBySeller['address']['altFirstName'] ?? '');
                        $recipientDataObject->setRecipientLname($shippingDataBySeller['address']['altLastName'] ?? '');
                        $recipientDataObject->setRecipientTelephone($shippingDataBySeller['address']['altPhoneNumber'] ?? '');
                        $recipientDataObject->setRecipientEmail($shippingDataBySeller['address']['altEmail'] ?? '');
                        $recipientDataObject->setRecipientExt($shippingDataBySeller['address']['altPhoneNumberext'] ?? '');
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

                    $receipients[] =
                        [
                            self::CONTACT => [
                                self::CONTACTID => null,
                                self::PERSONNAME => [
                                    self::FIRSTNAME => !empty($recipientDataObject->getRecipientFname()) ? $recipientDataObject->getRecipientFname() : $recipientDataObject->getFName(),
                                    self::LASTNAME => !empty($recipientDataObject->getRecipientLname()) ? $recipientDataObject->getRecipientLname() : $recipientDataObject->getLname(),
                                ],
                                self::COMPANY => [
                                    'name' => 'FXO',
                                ],
                                self::EMAILDETAIL => [
                                    self::EMAILADDRESS => !empty($recipientDataObject->getRecipientEmail()) ? $recipientDataObject->getRecipientEmail() : $recipientDataObject->getEmail(),
                                ],
                                self::PHONENUMBERDETAILS => [
                                    0 => [
                                        self::PHONENUMBER => [
                                            self::NUMBER => trim(!empty($recipientDataObject->getRecipientTelephone()) ?
                                                (string)$recipientDataObject->getRecipientTelephone() : (string)$recipientDataObject->getTelephone()),
                                            'extension' => !empty($recipientDataObject->getRecipientExt()) ? $recipientDataObject->getRecipientExt() : $recipientDataObject->getExtension(),
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'reference' => $shippingDataBySeller['reference_id'],
                            'externalDelivery' => [
                                'address' => [
                                    self::STREET_LINES => $recipientDataObject->getStreetAddress(),
                                    'city' => $recipientDataObject->getCity(),
                                    self::STATE_OR_PROVINCE_CODE =>
                                        is_object($recipientDataObject->getShipperRegion())?
                                            $recipientDataObject->getShipperRegion()->getData('code'):(string)($recipientDataObject->getShipperRegion()),
                                    self::POSTALCODE => $recipientDataObject->getZipcode(),
                                    self::COUNTRYCODE => 'US',
                                    self::ADDRESS_CLASSIFICATION => $recipientDataObject->getAddressClassification(),
                                ],
                                'originAddress' => [
                                    'streetLines' => [
                                        $shopArrayData["additional_info"]['contact_info']['street_1']
                                    ],
                                    'city' => $shopArrayData["additional_info"]['contact_info']['city'],
                                    'stateOrProvinceCode' => $regionCode,
                                    'postalCode' => $shopArrayData["additional_info"]['contact_info']['zip_code'],
                                    'countryCode' => strtoupper($shopArrayData["additional_info"]["shipping_zones"][0]),
                                    'addressClassification' => $recipientDataObject->getAddressClassification()
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
                            'productAssociations' => $this->filterProductAssociation($itemData[$key], true)
                        ];
                }
            }
        }

        return $receipients;
    }


    /**
     * Filter production collection if Marketplace or not
     * @param array $products
     * @param bool $isMarketPlaceProduct
     * @return array
     */
    private function filterProductAssociation(array $products, bool $isMarketPlaceProduct): array
    {
        $associatedProducts = array_filter($products, function ($var) use ($isMarketPlaceProduct) {
            return ($var['is_marketplace'] == $isMarketPlaceProduct);
        });

        return array_values($associatedProducts);
    }

    /**
     * Build a data object with Receipient Info
     * @return object
     */
    private function prepareReceipientDataObject(
        $quote,
        $shipmentId,
        $recipientFname,
        $fName,
        $recipientLname,
        $lName,
        $recipientEmail,
        $email,
        $companyName,
        $recipientTelephone,
        $telephone,
        $recipientExt,
        $extension,
        $streetAddress,
        $city,
        $shipperRegion,
        $zipcode,
        $addressClassification,
        $shipMethod,
        $fedexShipAccountNumber,
        $poReferenceId,
        $productAssociations,
        $contactId,
        $locationId,
        $requestedPickupLocalTime
    ): object
    {
        $isCompanyNameToggleEnabled = $this->toggleConfig->getToggleConfigValue('enable_fixing_fxo_appears_in_company_name_for_shipping_flow');
        $dataObject = $this->dataObjectFactory->create();
        $dataObject->setQuote($quote);
        $dataObject->setShipmentId($shipmentId);
        $dataObject->setRecipientFname($recipientFname);
        $dataObject->setFName($fName);
        $dataObject->setRecipientLname($recipientLname);
        $dataObject->setLname($lName);
        $dataObject->setRecipientEmail($recipientEmail);
        $dataObject->setEmail($email);
        if($isCompanyNameToggleEnabled) {
            $dataObject->setCompanyName($companyName);
        }
        $dataObject->setRecipientTelephone($recipientTelephone);
        $dataObject->setTelephone($telephone);
        $dataObject->setRecipientExt($recipientExt);
        $dataObject->setExtension($extension);
        $dataObject->setStreetAddress($streetAddress);
        $dataObject->setCity($city);
        $dataObject->setShipperRegion($shipperRegion);
        $dataObject->setZipcode($zipcode);
        $dataObject->setAddressClassification($addressClassification);
        $dataObject->setShipMethod($shipMethod);
        $dataObject->setFedexShipAccountNumber($fedexShipAccountNumber);
        $dataObject->setPoReferenceId($poReferenceId);
        $dataObject->setProductAssociations($productAssociations);
        $dataObject->setContactId($contactId);
        $dataObject->setLocationId($locationId);
        $dataObject->setRequestedPickupLocalTime($requestedPickupLocalTime);

        return $dataObject;
    }

    private function getCustomReceipientAttributes(array $customAttributes): array
    {
        $email = '';
        $ext = '';
        foreach ($customAttributes as $attribute) {
            if ($attribute['attribute_code'] == 'email_id') {
                $email = $attribute['value'];
            }
            if ($attribute['attribute_code'] == 'ext') {
                $ext = $attribute['value'];
            }
        }
        return [
            'email' => $email,
            'ext' => $ext
        ];
    }

    /**
     * @param $recipientDataObject
     * @param $isOrderApproval
     * @return array|array[]|mixed
     */
    private function getReciepientsData($recipientDataObject,$isPickup=false,$isOrderApproval=false)
    {
        return $this->getReceipientInfoUpdated($isPickup, $recipientDataObject, $isOrderApproval);
    }

    private function getFedexLocationId() {
        $fedexLocationId = null;
        if($this->crData->isRetailCustomer() && (!$this->requestQueryValidator->isGraphQl())){
            $fedexLocationId = $this->crData->getLocationCode();
        }

        return $fedexLocationId;
    }

    /**
     * Toggle for D-200699 also fix D-200854
     *
     * @return bool|int
     */
    private function isD200699Enabled()
    {
        return $this->toggleConfig->getToggleConfigValue('d200699_d200854');
    }
}
