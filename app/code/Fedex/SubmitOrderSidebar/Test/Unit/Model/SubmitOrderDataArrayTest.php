<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model;

use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Model\Organization;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\SubmitOrderSidebar\Api\BillingFieldBuilderInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderDataArray;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Directory\Model\Region;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Fedex\InBranch\Model\InBranchValidation;
use Magento\Quote\Model\Quote\Address;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\SubmitOrderSidebar\Api\Data\BillingFieldCollectionInterface;

class SubmitOrderDataArrayTest extends TestCase
{
    protected $quoteHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected $graphQlHelperMock;
    /**
     * @var (\Fedex\InBranch\Model\InBranchValidation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inBranchValidationMock;
    protected $toggleConfigMock;
    protected $submitOrderDataArrayMock;
    protected $regionMock;
    private MockObject|DataObjectFactory $dataObjectFactory;

    /**
     * @var MockObject|RequestQueryValidator
     */
    private MockObject|RequestQueryValidator $requestQueryValidatorMock;

    /** @var CartInterface */
    private CartInterface $quote;

    /** @var BillingFieldBuilderInterface  */
    private BillingFieldBuilderInterface $billingFieldBuilder;

    /** @var MockObject|Quote  */
    private MockObject|Quote $quoteObject;

    /** @var Organization  */
    private Organization $organization;

    /** @var Address  */
    private Address $address;

    /** @var ToggleConfig  */
    protected $toggleConfig;

    /**
     * @return void
     */
    public function setup():void
    {
        $this->quote = $this->createMock(CartInterface::class);
        $this->billingFieldBuilder = $this->createMock(BillingFieldBuilderInterface::class);
        $billingFieldCollection = $this->getMockBuilder(BillingFieldCollectionInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['hasPoNumber', 'toArrayApi'])
            ->getMock();
        $billingFieldCollection->expects($this->any())->method('hasPoNumber')->willReturn(false);
        $billingFieldCollection->expects($this->any())->method('toArrayApi')->willReturn(false);
        $this->billingFieldBuilder->expects($this->any())->method('build')->willReturn($billingFieldCollection);

        $this->quoteHelper = $this->createMock(QuoteHelper::class);
        $this->quoteHelper->expects($this->any())->method('isMiraklQuote')->willReturn(true);
        $this->quoteHelper->expects($this->any())->method('isFullMiraklQuote')->willReturn(false);
        $this->quoteObject = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->organization = $this->createMock(Organization::class);
        $this->organization->method('getOrganization')->willReturn('FXO');
        $this->address = $this->createMock(Address::class);
        $this->quoteObject->method('getShippingAddress')->willReturn($this->address);
        $this->address->method('getCompany')->willReturn('FXO');

        $this->graphQlHelperMock = $this->getMockBuilder(\Fedex\GraphQl\Helper\Data::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getJwtParamByKey']);

        $this->requestQueryValidatorMock = $this->createMock(RequestQueryValidator::class);

        $dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->setMethods(['getQuote', 'getDate','getFjmpRateQuoteId','getFname',
                'getLname','getCompanyName','getEmail',
                'getPhNumber','getExtension', 'getRecipientFname', 'getRecipientLname',
                'getRecipientEmail', 'getRecipientTelephone', 'getRecipientExtension', 'getRecipientExt', 'getPickStore',
                'getFedExAccountNumber','getOrderNumber','getCompanySite',
                'getUserReferences','getTelephone', 'getPreviousQuoteIdToggle',
                'getWebhookUrl','getProductData','getShipmentId',
                'getProductAssociations','getPromoCodeArray','getPoReferenceId',
                'getStreetAddress','getCity','getShipperRegion','getZipCode',
                'getAddressClassification','getShipMethod',
                'getFedexShipAccountNumber','getLocationId','getRequestedPickupLocalTime',
                'getNumDiscountPrice','getShippingAccount','getRequestedAmount',
                'getEncCCData','getCcToken','getNameOnCard','getExpirationMonth',
                'getExpirationYear','getNumTotal','getState',
                'getAccNo','getCondition','getPaymentMethod','getStateCode', 'getRateQuoteId',
                'getSourceRetailLocationId', 'getOrderClient', 'getNotes', 'getContactId', 'setQuote', 'setShipmentId', 'setRecipientFname', 'setFName',
                'setRecipientLname', 'setLname', 'setRecipientEmail', 'setEmail', 'setRecipientTelephone',
                'setTelephone', 'setRecipientExt', 'setExtension', 'setStreetAddress', 'setCity',
                'setShipperRegion', 'setZipcode', 'setAddressClassification', 'setShipMethod',
                'setFedexShipAccountNumber', 'setPoReferenceId', 'setProductAssociations', 'setContactId',
                'setLocationId', 'setRequestedPickupLocalTime', 'getSiteName','getIsB2bApproval','getLteIdentifier'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataObjectFactory->expects($this->any())->method('getRecipientExt')->willReturn(false);

        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->addMethods(['getAdditionalData'])
            ->getMock();
        $itemMock->expects($this->any())->method('getAdditionalData')->willReturn(['mirakl_shipping_data' => 'test']);
        $this->quoteObject->expects($this->any())->method('getAllItems')->willReturn([$itemMock]);

        $this->dataObjectFactory->expects($this->any())->method('getQuote')->willReturn($this->quoteObject);
        $dataObjectFactory->expects($this->any())->method('create')->willReturn($this->dataObjectFactory);

        $this->dataObjectFactory->expects($this->any())->method('setQuote')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setShipmentId')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRecipientFname')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setFName')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRecipientLname')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setLname')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRecipientEmail')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setEmail')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRecipientTelephone')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setTelephone')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRecipientExt')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setExtension')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setStreetAddress')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setCity')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setShipperRegion')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setZipcode')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setAddressClassification')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setShipMethod')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setFedexShipAccountNumber')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setPoReferenceId')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setProductAssociations')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setContactId')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setLocationId')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('setRequestedPickupLocalTime')->willReturnSelf();
        $this->dataObjectFactory->expects($this->any())->method('getIsB2bApproval')->willReturn(true);

         $this->inBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->submitOrderDataArrayMock = (new ObjectManager($this))->getObject(SubmitOrderDataArray::class, [
            'helper' => $this->graphQlHelperMock,
            'requestQueryValidator' => $this->requestQueryValidatorMock,
            'billingFieldBuilder' => $this->billingFieldBuilder,
            'dataObjectFactory' => $dataObjectFactory,
            'quoteHelper' => $this->quoteHelper,
            'inbranchvalidation'=>$this->inBranchValidationMock,
            'organization' => $this->organization,
            'toggleConfig' => $this->toggleConfigMock
        ]);

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->setMethods(['load','getCode','getData'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return void
     */
    public function testGetTransactionOrderDetails()
    {
        $data = [
            'checkoutRequest' => [
                'transactionHeader' => [
                    'requestDateTime' => '',
                    'rateQuoteId' => '',
                    'type' => "SALE",
                ],
                'transactionReceiptDetails' => [
                    'receiptType' => "NONE",
                    'receiptFormat' => "STANDARD",
                ],
                'contact' => [
                    'contactId' => null,
                    'personName' => [
                        'firstName' => '',
                        'lastName' => '',
                    ],
                    'company' => [
                        'name' => '',
                    ],
                    'emailDetail' => [
                        'emailAddress' => '',
                    ],
                    'phoneNumberDetails' => [
                        0 => [
                            'phoneNumber' => [
                                'number' => '',
                                'extension' => '',
                            ],
                            'usage' => 'PRIMARY',
                        ],
                    ],
                ],
                'tenders' => [],
            ],
        ];

        $this->assertNotNull(
            $this->submitOrderDataArrayMock->getTransactionOrderDetails($this->dataObjectFactory,$this->quoteObject)
        );
    }

    /**
     * @return void
     */
    public function testGetTransactionOrderDetailsif()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $data = [
            'checkoutRequest' => [
                'transactionHeader' => [
                    'requestDateTime' => '',
                    'rateQuoteId' => '',
                    'type' => "SALE",
                ],
                'transactionReceiptDetails' => [
                    'receiptType' => "EMAIL",
                    'receiptFormat' => "INVOICE_EIGHT_BY_ELEVEN",
                ],
                'contact' => [
                    'contactId' => null,
                    'personName' => [
                        'firstName' => '',
                        'lastName' => '',
                    ],
                    'company' => [
                        'name' => '',
                    ],
                    'emailDetail' => [
                        'emailAddress' => '',
                    ],
                    'phoneNumberDetails' => [
                        0 => [
                            'phoneNumber' => [
                                'number' => '',
                                'extension' => '',
                            ],
                            'usage' => 'PRIMARY',
                        ],
                    ],
                ],
                'tenders' => [],
            ],
        ];
        $this->assertNotNull(
            $this->submitOrderDataArrayMock->getTransactionOrderDetails($this->dataObjectFactory,$this->quoteObject)
        );
    }

    /**
     * @return void
     */
    public function testGetOrderDetails()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPickStore')->willReturn(0);
        $this->dataObjectFactory->expects($this->any())->method('getShipperRegion')->willReturn($this->regionMock);
        $this->dataObjectFactory->expects($this->any())->method('getOrderClient')->willReturn('MAGENTO');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())->method('getLteIdentifier')->willReturn('lte-66666');
        $this->dataObjectFactory->expects($this->any())
            ->method('getProductAssociations')
            ->willReturn([
                [
                    [
                        'id'            => 0,
                        'quantity'      => 5,
                        'is_marketplace'=> false,
                    ],
                ],
            ]);

        $this->regionMock->expects($this->any())->method('getData')->with('code')->willReturn('TX');
        $output = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => null,
                'action' => 'SAVE_COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => null,
                    'lteIdentifier' => 'lte-66666',
                    'origin' => [
                        'orderNumber' => "",
                        'orderClient' => 'MAGENTO',
                        'site' => "",
                        'siteName'=>"",
                        'userReferences' => "",
                        'fedExLocationId' => null
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => '',
                            'personName' => [
                                'firstName' => "",
                                'lastName' => "",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => '',
                                        'extension' => '',
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => "",
                            'auth' => '',
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => null,
                    'recipients' => [
                        0 => [
                            'reference' => null,
                            'contact' => [
                                'contactId' => '',
                                'personName' => [
                                    'firstName' => "",
                                    'lastName' => "",
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => "",
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => "",
                                            'extension' => '',
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => null,
                                    'city' => null,
                                    'stateOrProvinceCode' => 'TX',
                                    'postalCode' => null,
                                    'countryCode' => 'US',
                                    'addressClassification' => null,
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => null,
                                'fedExAccountNumber' => null,
                                'deliveryInstructions' => null,
                                'poNumber' => null,
                                'productionLocationId' => null,
                            ],
                            'productAssociations' => [],
                        ],
                    ],
                    'notes' => null,
                ],
                'coupons' => null,
                'teamMemberId' => null,
            ],
        ];

        $this->assertNotEquals(
            $output,
            $this->submitOrderDataArrayMock->getOrderDetails(
                $this->dataObjectFactory,
                $this->quoteObject,
                true,
                false
            ));
    }

    /**
     * @return void
     */
    public function testGetOrderDetailsWithCommit()
    {
        $this->dataObjectFactory->expects($this->any())->method('getLteIdentifier')->willReturn('lte-66666');
        $this->dataObjectFactory->expects($this->any())->method('getPickStore')->willReturn(0);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteId')->willReturn('test');
        $this->dataObjectFactory->expects($this->any())->method('getShipperRegion')->willReturn($this->regionMock);
        $this->dataObjectFactory->expects($this->any())->method('getOrderClient')->willReturn('MAGENTO');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())
            ->method('getProductAssociations')
            ->willReturn([
                [
                    [
                        'id'            => 0,
                        'quantity'      => 5,
                        'is_marketplace'=> false,
                    ],
                ],
            ]);

        $this->regionMock->expects($this->any())->method('getCode')->willReturn('TX');
        $output = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => 'test',
                'action' => 'COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => null,
                    'lteIdentifier' => 'lte-66666',
                    'origin' => [
                        'orderNumber' => "",
                        'orderClient' => 'MAGENTO',
                        'site' => "",
                        'siteName'=>"",
                        'userReferences' => "",
                        'fedExLocationId' => null
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => '',
                            'personName' => [
                                'firstName' => "",
                                'lastName' => "",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => '',
                                        'extension' => '',
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => "",
                            'auth' => '',
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => null,
                    'recipients' => [
                        0 => [
                            'reference' => null,
                            'contact' => [
                                'contactId' => '',
                                'personName' => [
                                    'firstName' => "",
                                    'lastName' => "",
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => "",
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => "",
                                            'extension' => '',
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => null,
                                    'city' => null,
                                    'stateOrProvinceCode' => 'TX',
                                    'postalCode' => null,
                                    'countryCode' => 'US',
                                    'addressClassification' => null,
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => null,
                                'fedExAccountNumber' => null,
                                'deliveryInstructions' => null,
                                'poNumber' => null,
                            ],
                            'productAssociations' => [],
                        ],
                    ],
                    'notes' => null,
                ],
                'coupons' => null,
                'teamMemberId' => null,
            ],
        ];

        $this->assertNotEquals(
            $output,
            $this->submitOrderDataArrayMock->getOrderDetails(
                $this->dataObjectFactory,
                $this->quoteObject,
                true,
                true
            ));
    }

    /**
     * @return void
     */
    public function testGetOrderDetailsWithRateQuoteIdNull()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPickStore')->willReturn(0);
        $this->dataObjectFactory->expects($this->any())->method('getRateQuoteId')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())->method('getShipperRegion')->willReturn($this->regionMock);
        $this->dataObjectFactory->expects($this->any())->method('getOrderClient')->willReturn('MAGENTO');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())
            ->method('getProductAssociations')
            ->willReturn([
                [
                    [
                        'id'            => 0,
                        'quantity'      => 5,
                        'is_marketplace'=> false,
                    ],
                ],
            ]);

        $this->regionMock->expects($this->any())->method('getCode')->willReturn('TX');
        $output = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => null,
                'action' => 'COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => null,
                    'origin' => [
                        'orderNumber' => "",
                        'orderClient' => 'MAGENTO',
                        'site' => "",
                        'siteName'=>"",
                        'userReferences' => "",
                        'fedExLocationId' => null
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => '',
                            'personName' => [
                                'firstName' => "",
                                'lastName' => "",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => '',
                                        'extension' => '',
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => "",
                            'auth' => '',
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => null,
                    'recipients' => [
                        0 => [
                            'reference' => null,
                            'contact' => [
                                'contactId' => '',
                                'personName' => [
                                    'firstName' => "",
                                    'lastName' => "",
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => "",
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => "",
                                            'extension' => '',
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'shipmentDelivery' => [
                                'address' => [
                                    'streetLines' => null,
                                    'city' => null,
                                    'stateOrProvinceCode' => 'TX',
                                    'postalCode' => null,
                                    'countryCode' => 'US',
                                    'addressClassification' => null,
                                ],
                                'holdUntilDate' => null,
                                'serviceType' => null,
                                'fedExAccountNumber' => null,
                                'deliveryInstructions' => null,
                                'poNumber' => null,
                            ],
                            'productAssociations' => [],
                        ],
                    ],
                    'notes' => null,
                ],
                'coupons' => null,
                'teamMemberId' => null,
            ],
        ];

        $this->assertNotEquals($output, $this->submitOrderDataArrayMock->getOrderDetails($this->dataObjectFactory, $this->quoteObject));
    }

    /**
     * @return void
     */
    public function testGetOrderPickupDetails()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPickStore')->willReturn(1);
        $this->dataObjectFactory->expects($this->any())->method('getOrderClient')->willReturn('MAGENTO');
        $this->dataObjectFactory->expects($this->any())->method('getSiteName')->willReturn(null);
        $this->dataObjectFactory->expects($this->any())->method('getLteIdentifier')->willReturn('lte-66666');
        $this->dataObjectFactory->expects($this->any())
            ->method('getProductAssociations')
            ->willReturn([[]]);

        $output = [
            'rateQuoteRequest' => [
                'sourceRetailLocationId' => null,
                'previousQuoteId' => null,
                'action' => 'SAVE_COMMIT',
                'retailPrintOrder' => [
                    'fedExAccountNumber' => null,
                    'lteIdentifier' => 'lte-66666',
                    'origin' => [
                        'orderNumber' => null,
                        'orderClient' => 'MAGENTO',
                        'site' => "",
                        'siteName'=>"",
                        'userReferences' => "",
                        'fedExLocationId' => null
                    ],
                    'orderContact' => [
                        'contact' => [
                            'contactId' => null,
                            'personName' => [
                                'firstName' => "",
                                'lastName' => "",
                            ],
                            'company' => [
                                'name' => 'FXO',
                            ],
                            'emailDetail' => [
                                'emailAddress' => "",
                            ],
                            'phoneNumberDetails' => [
                                0 => [
                                    'phoneNumber' => [
                                        'number' => '',
                                        'extension' => '',
                                    ],
                                    'usage' => 'PRIMARY',
                                ],
                            ],
                        ],
                    ],
                    'customerNotificationEnabled' => false,
                    'notificationRegistration' => [
                        'webhook' => [
                            'url' => '',
                            'auth' => '',
                        ],
                    ],
                    'profileAccountId' => null,
                    'expirationDays' => '30',
                    'products' => null,
                    'recipients' => [
                        0 => [
                            'reference' => null,
                            'contact' => [
                                'contactId' => '',
                                'personName' => [
                                    'firstName' => "",
                                    'lastName' => "",
                                ],
                                'company' => [
                                    'name' => 'FXO',
                                ],
                                'emailDetail' => [
                                    'emailAddress' => "",
                                ],
                                'phoneNumberDetails' => [
                                    0 => [
                                        'phoneNumber' => [
                                            'number' => "",
                                            'extension' => '',
                                        ],
                                        'usage' => 'PRIMARY',
                                    ],
                                ],
                            ],
                            'pickUpDelivery' => [
                                'location' => [
                                    'id' => '',
                                ],
                                'requestedPickupLocalTime' => null,
                                'requestedDeliveryLocalTime' => null
                            ],
                            'productAssociations' => [],
                        ],
                    ],
                    'notes' => null,
                ],
                'coupons' => null,
                'teamMemberId' => null,
            ],
        ];

        $this->assertEquals($output, $this->submitOrderDataArrayMock->getOrderDetails($this->dataObjectFactory, $this->quoteObject));
    }

    /**
     * @return void
     */
    public function testGetCheckoutRequestTenderData()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPaymentMethod')->willReturn('cc');
        $this->dataObjectFactory->expects($this->any())->method('getCondition')->willReturn(1);
        $this->dataObjectFactory->expects($this->any())->method('getShipperRegion')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $output = [
            0 => [
                'id' => "1",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::ACCOUNT,
                'requestedAmount' => null,
                'account' => [
                    'accountNumber' => null,
                    'responsibleParty' => SubmitOrderDataArray::RESPONSIBLE_PARTY,
                ],

            ],
            1 => [
                'id' => "2",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::CREDIT_CARD,
                'requestedAmount' => null,
                'creditCard' => [
                    'encryptedCreditCard' => null,
                    'token' => null,
                    'cardHolderName' => null,
                    'billingAddress' => [
                        'streetLines' => null,
                        'city' => null,
                        'stateOrProvinceCode' => 'TX',
                        'postalCode' => null,
                        'countryCode' => 'US',
                        'addressClassification' => null,
                    ],
                    'expirationMonth' => null,
                    'expirationYear' => null,
                ],
                'poNumber' => null,
            ],
        ];

        $this->assertEquals(
            $output,
            $this->submitOrderDataArrayMock->getCheckoutRequestTenderData($this->dataObjectFactory, $this->quote)
        );
    }

    /**
     * @return void
     */
    public function testGetCheckoutRequestTenderDataElse()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPaymentMethod')->willReturn('cc');
        $this->dataObjectFactory->expects($this->any())->method('getCondition')->willReturn(0);
        $this->dataObjectFactory->expects($this->any())->method('getShipperRegion')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('getCode')->willReturn('TX');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $output = [
            0 => [
                'id' => "1",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::CREDIT_CARD,
                'requestedAmount' => null,
                'creditCard' => [
                    'encryptedCreditCard' => null,
                    'token' => null,
                    'cardHolderName' => null,
                    'billingAddress' => [
                        'streetLines' => null,
                        'city' => null,
                        'stateOrProvinceCode' => 'TX',
                        'postalCode' => null,
                        'countryCode' => 'US',
                        'addressClassification' => null,
                    ],
                    'expirationMonth' => null,
                    'expirationYear' => null,
                ],
                'poNumber' => null,
            ],
        ];

        $this->assertEquals(
            $output,
            $this->submitOrderDataArrayMock->getCheckoutRequestTenderData($this->dataObjectFactory, $this->quote)
        );
    }

    /**
     * @return void
     */
    public function testGetCheckoutRequestTenderDataElseForPayment()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPaymentMethod')->willReturn('');
        $this->dataObjectFactory->expects($this->any())->method('getCondition')->willReturn(1);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $output = [
            0 => [
                'id' => "1",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::ACCOUNT,
                'requestedAmount' => null,
                'account' => [
                    'accountNumber' => null,
                    'responsibleParty' => "SENDER",
                ],
            ],
            1 => [
                'id' => "2",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::ACCOUNT,
                'requestedAmount' => null,
                'poNumber' => null,
                "account" => [
                    'accountNumber' => null,
                ],
            ],
        ];

        $this->assertEquals(
            $output,
            $this->submitOrderDataArrayMock->getCheckoutRequestTenderData($this->dataObjectFactory, $this->quote)
        );
    }

    /**
     * @return void
     */
    public function testGetCheckoutRequestTenderDataElseForPaymentElse()
    {
        $this->dataObjectFactory->expects($this->any())->method('getPaymentMethod')->willReturn('');
        $this->dataObjectFactory->expects($this->any())->method('getCondition')->willReturn(0);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);

        $output = [
            0 => [
                'id' => "1",
                'currency' => SubmitOrderDataArray::CURRENCY,
                'paymentType' => SubmitOrderDataArray::ACCOUNT,
                'requestedAmount' => null,
                'poNumber' => null,
                "account" => [
                    'accountNumber' => null,
                ],
            ],
        ];

        $this->assertEquals(
            $output,
            $this->submitOrderDataArrayMock->getCheckoutRequestTenderData($this->dataObjectFactory, $this->quote)
        );
    }

    public function testPreparePickupRateQuoteRequestData(): void
    {
        $this->dataObjectFactory->expects($this->any())
            ->method('getProductAssociations')
            ->willReturn([
                [
                    [
                        'id'            => 0,
                        'quantity'      => 5,
                        'is_marketplace'=> false,
                    ],
                ],
            ]);

        $this->dataObjectFactory->expects($this->any())
            ->method('getFedExAccountNumber')
            ->willReturn('1234');

        $this->dataObjectFactory->expects($this->any())
            ->method('getOrderNumber')
            ->willReturn('1234');

        $this->dataObjectFactory->expects($this->any())
            ->method('getCompanySite')
            ->willReturn('company-site-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getUserReferences')
            ->willReturn('user-references-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getFname')
            ->willReturn('f-name-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getLname')
            ->willReturn('l-name-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getEmail')
            ->willReturn('test@test.com');

        $this->dataObjectFactory->expects($this->any())
            ->method('getTelephone')
            ->willReturn('11111111111111');

        $this->dataObjectFactory->expects($this->any())
            ->method('getExtension')
            ->willReturn('extension-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getRecipientFname')
            ->willReturn('recipient-f-name-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getRecipientLname')
            ->willReturn('recipient-l-name-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getRecipientEmail')
            ->willReturn('test-recipient-email@test.com');

        $this->dataObjectFactory->expects($this->any())
            ->method('getRecipientTelephone')
            ->willReturn('22222222222222');

        $this->dataObjectFactory->expects($this->any())
            ->method('getRecipientExtension')
            ->willReturn('recipient-extension-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getWebhookUrl')
            ->willReturn('webhook-url-test');

        $this->dataObjectFactory->expects($this->any())
            ->method('getWebhookUrl')
            ->willReturn('webhook-url-test');

        $this->requestQueryValidatorMock->expects($this->any())
            ->method('isGraphQl')
            ->willReturn(true);

        $this->dataObjectFactory->expects($this->any())
            ->method('getContactId')
            ->willReturn('123456');

        $result = $this->submitOrderDataArrayMock->preparePickupRateQuoteRequestData(
            "123",
            "test-action",
            $this->dataObjectFactory,
            $this->quoteObject
        );

        static::assertIsArray($result);
        static::assertNotEmpty($result);
    }
}
