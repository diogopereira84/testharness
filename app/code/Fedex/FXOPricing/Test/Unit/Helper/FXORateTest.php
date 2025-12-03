<?php

/**
 * Php file,Test case for FXORate.
 *
 * @author  Ayush Sood <ayush.sood@infogain.com>
 * @license http://infogain.com Infogain License
 */

namespace Fedex\FXOPricing\Test\Unit\Helper;

use Fedex\Company\Helper\Data as CompanyHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Customer\Model\Session;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use Fedex\Cart\Helper\Data as CartDataHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory as QuoteItemCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Quote\Api\Data\CartInterface;
use Fedex\InBranch\Model\InBranchValidation;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Fedex\ExpiredItems\Helper\ExpiredData as ExpiredDataHelper;
use Fedex\Header\Helper\Data;

class FXORateTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Magento\Framework\Controller\Result\JsonFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resultJsonFactory;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     * Configuration interface used for accessing scope-specific configuration values.
     */
    protected $configInterface;
    /**
     * @var \Fedex\FXOPricing\Helper\DeliveryHelper
     * Helper class for handling delivery-related operations.
     */
    protected $deliveryHelper;
    /**
     * @var (\Magento\Directory\Model\RegionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $regionFactory;
    /**
     * @var (\Magento\Directory\Model\Region & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $region;
    /**
     * @var (\Magento\Framework\Model\AbstractModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractModel;
    /**
     * @var \Fedex\FXOPricing\Helper\CompanyHelper
     * Helper class instance for company-related operations.
     */
    protected $companyHelper;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     * Mock object for the company entity used in unit tests.
     */
    protected $companyMock;
    /**
     * @var (\Magento\Framework\Controller\Result\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $json;
    /**
     * @var \Fedex\FXOPricing\Helper\PunchoutHelper
     * Helper class instance used for punchout-related operations in the test.
     */
    protected $punchoutHelper;
    /**
     * @var \Magento\Catalog\Model\Product
     * Product model instance used for testing purposes.
     */
    protected $productModel;
    /**
     * @var \Magento\Eav\Api\AttributeSetRepositoryInterface
     * Attribute Set Repository Interface used for managing attribute sets.
     */
    protected $attributeSetRepositoryInterface;
    /**
     * @var \Magento\Eav\Api\AttributeSetInterface
     * AttributeSetInterface instance used for managing attribute sets in tests.
     */
    protected $attributeSetInterface;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     * Factory class for creating cart (quote) instances.
     */
    protected $cartFactory;
    /**
     * @var \Magento\Checkout\Model\Cart
     * Represents the cart instance used for testing purposes.
     */
    protected $cart;
    /**
     * @var \Fedex\FXOPricing\Helper\CartDataHelper
     * Helper class for accessing cart data.
     */
    protected $cartDataHelper;
    /**
     * @var \Fedex\FXOPricing\Helper\SDEHelper
     * Helper instance used for SDE-related operations in the test.
     */
    protected $sdeHelper;
    /**
     * @var \Magento\Quote\Model\Quote
     * Represents the quote object used in the test cases.
     */
    protected $quote;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject Mock object for item options used in unit tests.
     */
    protected $itemOptionMock;
    /**
     * @var mixed $item
     * Represents an item used in the unit test for FXORate functionality.
     */
    protected $item;
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     * Serializer instance used for handling data serialization and deserialization.
     */
    protected $serializer;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $logger;
    /**
     * @var (\Fedex\FXOPricing\Test\Unit\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $gateTokenHelper;
    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     * Curl client instance used for making HTTP requests in the test.
     */
    protected $curl;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $request;
    /**
     * @var (\Magento\Quote\Model\Quote\Address & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $address;
    /**
     * @var (\Fedex\InBranch\Model\InBranchValidation & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $inBranchValidationMock;
    /**
     * @var Magento\Framework\DataObject
     */
    protected $product;
    /**
     * @var mixed $option
     * This property is used to store options for the test cases.
     * The exact type and purpose of the options depend on the test implementation.
     */
    protected $option;
    /**
     * @var \Fedex\FXOPricing\Api\Data\OptionInterface
     * This property holds an instance of the OptionInterface, which is used
     * to interact with option-related data within the FXO Pricing module.
     */
    protected $optionInterface;
    /**
     * @var \Magento\Customer\Model\Session
     * Customer session instance used for managing customer-related data.
     */
    protected $customerSession;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\CollectionFactory
     * Factory for creating instances of quote item collections.
     */
    protected $quoteItemCollectionFactory;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Item\Collection
     * Collection of quote items used for testing purposes.
     */
    protected $quoteItemCollection;
    /**
     * Mock object for the checkout session.
     *
     * @var \Magento\Checkout\Model\Session|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutSessionMock;
    /**
     * @var \Fedex\FXOPricing\Helper\FXORate
     * Helper class instance for FXO Rate functionality.
     */
    protected $fXORateHelper;
    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * @var (\Magento\Framework\Message\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $messageManager;

    /**
     * @var (\Fedex\FXOPricing\Helper\ExpiredDataHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $expiredDataHelper;

    /**
     * @var (\Fedex\FXOPricing\Test\Unit\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $data;

    /**
     * @var (\Magento\Framework\App\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $config;

     /**
      * @var Api token key
      */
    protected $apiToken = [
        'token' => 'iuayqiuyeiuqwtyeiyqiuqywiueyqwiueyuqwi'
    ];

    /**
     * @var CartInterface
     */
    private $quoteRepositoryMock;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->context = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()->getMock();
        $this->configInterface = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->setMethods(['getValue'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()->setMethods([
                    'isCommercialCustomer',
                    'getCompanySite',
                    'getGateToken',
                    'getTazToken',
                    'getRateRequestShipmentSpecialServices'
                ])
            ->getMock();
        $this->regionFactory = $this->getMockBuilder(RegionFactory::class)->setMethods(['create', 'load'])
            ->disableOriginalConstructor()->getMock();
        $this->region = $this->getMockBuilder(Region::class)->setMethods(['load'])
            ->disableOriginalConstructor()->getMock();
        $this->abstractModel = $this->getMockBuilder(AbstractModel::class)->disableOriginalConstructor()
            ->setMethods(['getCode'])->getMock();
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)->disableOriginalConstructor()
            ->setMethods([
                    'getCompanyPaymentMethod',
                    'getFedexAccountNumber',
                    'getPaymentMethod',
                    'getFXOAccountNumber'
                ])
            ->getMock();
        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)->setMethods(['getCompanyName'])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->json = $this->getMockBuilder(Json::class)->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()->getMock();
        $this->punchoutHelper = $this->getMockBuilder(PunchoutHelper::class)->disableOriginalConstructor()
            ->setMethods(['getGatewayToken', 'getTazToken','getAuthGatewayToken','getGatewayClientID'])->getMock();
        $this->toggleConfigMock =
        $this->getMockBuilder(ToggleConfig::class)->setMethods([
            'getToggleConfigValue'
        ])
        ->disableOriginalConstructor()
        ->getMock();
        $this->productModel = $this->getMockBuilder(Product::class)->setMethods(['load'])
            ->disableOriginalConstructor()->getMock();
        $this->attributeSetRepositoryInterface = $this->getMockBuilder(AttributeSetRepositoryInterface::class)
            ->setMethods(['getPostValue', 'get'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->attributeSetInterface = $this->getMockBuilder(AttributeSetInterface::class)
            ->setMethods(['getAttributeSetName'])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->cart = $this->getMockBuilder(Cart::class)->setMethods(['getQuote', 'save'])
            ->disableOriginalConstructor()->getMock();
        $this->cartDataHelper = $this->getMockBuilder(CartDataHelper::class)
            ->setMethods([
                'decryptData',
                'encryptData',
                'getDltThresholdHours',
                'setDltThresholdHours'
                ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods($this->quoteSetMethodsValues())
            ->disableOriginalConstructor()->getMock();

        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->onlyMethods(['getValue', 'save'])
            ->addMethods(['getOptionId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemOptionMock->expects($this->any())->method('getValue')
            ->willReturn($this->getInfoBuyRequestMock());
        $this->itemOptionMock->expects($this->any())->method('getOptionId')->willReturn(null);
        $this->item = $this->getMockBuilder(Item::class)
            ->setMethods($this->itemSetMethodsValues())
            ->disableOriginalConstructor()->getMock();
        $this->item->expects($this->any())->method('getAdditionalData')
            ->willReturn('{"is_marketplace_mocked": true}');
        $this->serializer = $this->getMockBuilder(SerializerInterface::class)
            ->disableOriginalConstructor()->setMethods(['serialize'])->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->gateTokenHelper = $this->getMockBuilder(Data::class)
            ->setMethods(['getTazToken', 'getGatewayToken'])->disableOriginalConstructor()
            ->getMock();
        $this->curl = $this->getMockBuilder(Curl::class)
            ->setMethods(['getBody', 'setOptions', 'post'])->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)->setMethods(['getPostValue'])
            ->getMockForAbstractClass();
        $this->address = $this->getMockBuilder(Address::class)
            ->setMethods(['getData'])->disableOriginalConstructor()->getMock();
          $this->inBranchValidationMock = $this->getMockBuilder(InBranchValidation::class)
           ->disableOriginalConstructor()->getMock();
        $this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods(
                [
                    'isCommercialCustomer',
                    'isEproCustomer',
                    'getCompanySite',
                    'getGateToken',
                    'getApiToken',
                    'getRateRequestShipmentSpecialServices'
                ]
            )
            ->disableOriginalConstructor()->getMock();
        $this->companyHelper    = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods(
                ['getCompanyPaymentMethod',
                    'getFedexAccountNumber',
                    'getPaymentMethod',
                    'getFXOAccountNumber',
                    'getCustomerCompany']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()->setMethods(['getProduct', 'setIsSuperMode', 'getId', 'getAttributeSetId'])
            ->getMock();
        $this->option = $this->getMockBuilder(Option::class)
            ->disableOriginalConstructor()->setMethods(['getOptionId', 'setValue', 'getValue', 'getOptionByCode'])
            ->getMock();
        $this->optionInterface = $this->getMockBuilder(OptionInterface::class)
            ->setMethods(['getValue', 'getProductId'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerSession = $this->getMockBuilder(Session::class)
            ->setMethods([
                'setPromoErrorMessage',
                'setFedexAccountWarning',
                'unsValidateContentApiExpired',
                'getExpiredItemIds',
                'setValidateContentApiExpired'
            ])
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $this->quoteItemCollectionFactory = $this->getMockBuilder(QuoteItemCollectionFactory::class)
            ->setMethods(['create'])->disableOriginalConstructor()->getMock();
        $this->quoteItemCollection = $this->getMockBuilder(QuoteItemCollection::class)
            ->disableOriginalConstructor()->setMethods(['addFieldToSelect', 'getSize', 'addFieldToFilter'])
            ->getMock();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)->setMethods([
                'getToggleConfigValue', 'getFedexAccountNumber', 'getFedexAccountNumberForShipping'
                ])
            ->disableOriginalConstructor()->getMock();
        $this->checkoutSessionMock = $this
            ->getMockBuilder(CheckoutSession::class)->setMethods([
                'getData',
                'setShippingCost',
                'getRemoveFedexAccountNumber',
                'setServiceType'
                ])
            ->disableOriginalConstructor()->getMock();
        $this->quoteRepositoryMock = $this->getMockBuilder(CartInterface::class)
            ->setMethods(['save'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->setMethods(['isRateQuoteProductAssociationEnabled'])->disableOriginalConstructor()
            ->getMockForAbstractClass();
            
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->expiredDataHelper = $this->createMock(ExpiredDataHelper::class);
        $this->data = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAuthHeaderValue', 'getGatewayToken'])
            ->getMock();

        $this->fXORateHelper = $this->objectManager->getObject(
            FXORate::class,
            [
                'context' => $this->context,
                'configInterface'=> $this->configInterface,
                'logger' => $this->logger,
                'deliveryHelper' => $this->deliveryHelper,
                'companyHelper' => $this->companyHelper,
                'punchoutHelper' => $this->punchoutHelper,
                'productModel' => $this->productModel,
                'attributeSetRepositoryInterface' => $this->attributeSetRepositoryInterface,
                'serializer' => $this->serializer,
                'curl' => $this->curl,
                'cart' => $this->cart,
                'cartFactory' => $this->cartFactory,
                'request' => $this->request,
                'regionFactory' => $this->regionFactory,
                'customerSession' => $this->customerSession,
                'toggleConfig' => $this->toggleConfigMock,
                'messageManager' => $this->messageManager,
                'quoteItemCollectionFactory' => $this->quoteItemCollectionFactory,
                'checkoutSession' => $this->checkoutSessionMock,
                'cartDataHelper' => $this->cartDataHelper,
                'sdeHelper' => $this->sdeHelper,
                'quoteRepository' => $this->quoteRepositoryMock,
                'expiredDataHelper' => $this->expiredDataHelper,
                'data' => $this->data,
                'config' => $this->config
            ]
        );
    }

    /**
     * Item setMethods values
     *
     * @return array
     */
    private function itemSetMethodsValues()
    {
        return [
            'getMiraklOfferId',
            'saveItemOptions',
            'setCustomPrice',
            'setQty',
            'setRowTotal',
            'setOriginalCustomPrice',
            'setInstanceId',
            'save',
            'setDiscount',
            'setBaseRowTotal',
            'setIsSuperMode',
            'getOptionByCode',
            'removeOption',
            'getQty',
            'getProduct',
            'getInstanceId',
            'getItemId',
            'getCustomPrice',
            'getAdditionalData'
        ];
    }

    /**
     * Quote setMethods values
     *
     * @return array
     */
    private function quoteSetMethodsValues()
    {
        return [
            'getAllItems',
            'deleteItem',
            'getData',
            'getShippingAddress',
            'save',
            'getIsFromShipping',
            'getIsFromPickup',
            'getIsFromAccountScreen',
            'getAllVisibleItems',
            'getCustomerPickupLocationData',
            'getCustomerShippingAddress',
            'getCouponCode',
            'setData',
            'setSubtotal',
            'setPromoDiscount',
            'setAccountDiscount',
            'setVolumeDiscount',
            'setShippingDiscount'
        ];
    }

    /**
     * Product rates data
     *
     * @return array
     */
    public function productRatesData()
    {
        return [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId'  => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => '(2.00)']
                                    ]
                                ]
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type'   => 'ACCOUNT',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test updateShippingPickupDetail.
     *
     * @return void
     */
    public function testupdateShippingPickupDetail()
    {
        $productRates = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => '0',
                                    'productId' => '1447174746733',
                                    'retailPrice' => '$0.59',
                                    'discountAmount' => '($0.05)',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.54',
                                    'lineDiscounts' => [
                                        0 => [
                                            'amount' => '($0.05)',
                                            'type' => 'ACCOUNT',
                                        ],
                                    ],
                                    'priceable' => true,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.54',
                                            'detailDiscountPrice' => '($0.05)',
                                            'detailUnitPrice' => '$0.5900',
                                            'detailDiscountedUnitPrice' => '($0.05)',
                                            'detailDiscounts' => [
                                                0 => [
                                                    'amount' => '($0.05)',
                                                    'type' => 'AR_CUSTOMERS',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'productRetailPrice' => '$0.59',
                                    'productDiscountAmount' => '($0.05)',
                                    'productLinePrice' => '$0.54',
                                    'productLineDiscounts' => [
                                        0 => [
                                            'amount' => '($0.05)',
                                            'type' => 'ACCOUNT',
                                        ],
                                    ],
                                    'editable' => false,
                                ],
                            ],
                            'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => '(2.00)']
                                    ],
                                    'deliveryRetailPrice' => '$0.00',
                                ]
                            ],
                            'grossAmount' => '$20.58',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'ACCOUNT',
                                ],
                            ],
                            'totalDiscountAmount' => '($0.05)',
                            'netAmount' => '$20.53',
                            'taxableAmount' => '$20.53',
                            'taxAmount' => '$0.04',
                            'totalAmount' => '$20.57',
                            'estimatedVsActual' => 'ACTUAL',
                        ],
                    ],
                ],
            ],
        ];
        $productRates = json_encode($productRates);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['setGrandTotal', 'setBaseGrandTotal', 'setCustomTaxAmount', 'save'])
            ->getMock();
        $quote->expects($this->any())
            ->method('setGrandTotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setBaseGrandTotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setCustomTaxAmount')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('save')
            ->willReturn($quote);
        $this->checkoutSessionMock->expects($this->any())->method('setShippingCost')
            ->willReturnSelf();
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(1);
        $this->assertNull(
            $this->fXORateHelper->updateShippingPickupDetail($quote, json_decode($productRates, true))
        );
    }

    /**
     * Test getRateApiUrl.
     *
     * @return void
     */
    public function testgetRateApiUrl()
    {
        $endPointUrl = "https://staging3.office.fedex.com/";
        $this->configInterface->expects($this->any())
            ->method('getValue')
            ->willReturn($endPointUrl);

        $this->assertNull(
            $this->assertEquals($endPointUrl, $this->fXORateHelper->getRateApiUrl())
        );
    }

    /**
     * Test UpdateRateForAccount.
     *
     * @return void
     */
    public function testUpdateRateForAccount()
    {
        $productRates = json_encode($this->productRatesData()['output']['rate']['rateDetails'][0]['discounts'] = []);
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'save', 'getAllVisibleItems'])
            ->getMock();
        $itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getItemId',
                    'setDiscountAmount',
                    'setBaseDiscountAmount',
                    'setRowTotal',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setIsSuperMode',
                    'getOptionByCode',
                    'removeOption',
                    'getQty',
                    'setDiscount',
                    'getProduct',
                    'save'
                ]
            )
            ->getMock();
        $product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'setIsSuperMode'])
            ->getMock();

        $quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $itemMock]);
        $itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $getValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($getValue));
        $itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $itemMock->expects($this->any())->method('setDiscountAmount')->willReturn($this);
        $itemMock->expects($this->any())->method('setBaseDiscountAmount')->willReturn($this);
        $itemMock->expects($this->any())->method('setDiscount')->willReturn($this);
        $itemMock->expects($this->any())->method('getProduct')->willReturn($product);
        $product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $itemMock->expects($this->any())->method('setRowTotal')->willReturn($this);
        $itemMock->expects($this->any())->method('save')->willReturn($itemMock);

        $this->assertNull(
            $this->fXORateHelper->updateRateForAccount(json_decode($productRates, true), $quote)
        );
    }

    /**
     * Test UpdateRateForAccount With deliveryLines.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testUpdateRateForAccountWithdeliveryLines()
    {
        $productRates = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => '0',
                                    'productId' => '1447174746733',
                                    'retailPrice' => '$0.59',
                                    'discountAmount' => '($0.05)',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.54',
                                    'lineDiscounts' => [
                                        0 => [
                                            'amount' => '($0.05)',
                                            'type' => 'ACCOUNT',
                                        ],
                                    ],
                                    'priceable' => true,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.54',
                                            'detailDiscountPrice' => '($0.05)',
                                            'detailUnitPrice' => '$0.5900',
                                            'detailDiscountedUnitPrice' => '($0.05)',
                                            'detailDiscounts' => [
                                                0 => [
                                                    'amount' => '($0.05)',
                                                    'type' => 'AR_CUSTOMERS',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'productRetailPrice' => '$0.59',
                                    'productDiscountAmount' => '($0.05)',
                                    'productLinePrice' => '$0.54',
                                    'productLineDiscounts' => [
                                        0 => [
                                            'amount' => '($0.05)',
                                            'type' => 'ACCOUNT',
                                        ],
                                    ],
                                    'editable' => false,
                                ],
                            ],
                            'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => '(2.00)']
                                    ],
                                    'deliveryRetailPrice' => '$0.00',
                                ]
                            ],
                            'grossAmount' => '$20.58',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'ACCOUNT',
                                ],
                            ],
                            'totalDiscountAmount' => '($0.05)',
                            'netAmount' => '$20.53',
                            'taxableAmount' => '$20.53',
                            'taxAmount' => '$0.04',
                            'totalAmount' => '$20.57',
                            'estimatedVsActual' => 'ACTUAL',
                        ],
                    ],
                ],
            ],
        ];

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'save', 'getAllVisibleItems'])
            ->getMock();
        $itemMock   = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getItemId',
                    'setDiscountAmount',
                    'setBaseDiscountAmount',
                    'setRowTotal',
                    'setCustomPrice',
                    'setOriginalCustomPrice',
                    'setIsSuperMode',
                    'getOptionByCode',
                    'removeOption',
                    'getQty',
                    'setDiscount',
                    'getProduct',
                    'save'
                ]
            )
            ->getMock();
        $product = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getProduct', 'setIsSuperMode'])
            ->getMock();
        $quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $itemMock]);
        $itemMock->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $getValue = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => ''
                    ],
                ],
            ],
        ];
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($getValue));
        $itemMock->expects($this->any())->method('getItemId')->willReturn(0);
        $itemMock->expects($this->any())->method('setDiscountAmount')->willReturn($this);
        $itemMock->expects($this->any())->method('setBaseDiscountAmount')->willReturn($this);
        $itemMock->expects($this->any())->method('setDiscount')->willReturn($this);
        $itemMock->expects($this->any())->method('getProduct')->willReturn($product);
        $product->expects($this->any())->method('setIsSuperMode')->willReturn(true);
        $itemMock->expects($this->any())->method('setRowTotal')->willReturn($this);
        $itemMock->expects($this->any())->method('save')->willReturn($itemMock);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(1);
        $this->assertNull(
            $this->fXORateHelper->updateRateForAccount($productRates, $quote)
        );
    }

    /**
     * Test UpdateQuoteDiscount.
     *
     * @return void
     */
    public function testUpdateQuoteDiscount()
    {
        $quote  = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setDiscount',
                    'setCouponCode',
                    'setSubtotal',
                    'setBaseSubtotal',
                    'setGrandTotal',
                    'setBaseGrandTotal',
                    'save'
                ]
            )
            ->getMock();
        $productRates = json_encode($this->productRatesData());
        $couponCode = 'MGT001';
        $quote->expects($this->any())
            ->method('setDiscount')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setCouponCode')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setSubtotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setBaseSubtotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setGrandTotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('setBaseGrandTotal')
            ->willReturn($this);
        $quote->expects($this->any())
            ->method('save')
            ->willReturn($quote);

        $this->assertNotNull(
            $this->fXORateHelper->updateQuoteDiscount($quote, json_decode($productRates, true), $couponCode)
        );
    }

    /**
     * Test UpdateCartItems.
     *
     * @return void
     */
    public function testUpdateCartItems()
    {
        $productRatesJson = json_encode($this->productRatesData());
        $productRates     = json_decode($productRatesJson, true);
        $itemUpdatedData = [0 => [1, 2, 3, 4, 5, 6]];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('setDiscount')->with('0.00')->willReturnSelf();
        $this->item->expects($this->any())->method('setBaseRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setOriginalCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setIsSuperMode')->with(true)->willReturnSelf();
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->serializer->expects($this->any())->method('serialize')->willReturn('AnyString');
        $this->option->expects($this->any())->method('setvalue')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturn($this->quote);
        $this->item->expects($this->any())->method('getInstanceId')->willReturn(0);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(0);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];
        $this->optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $quoteObjectItemsCount = 10;
        $dbQuoteItemCount = 11;
        $this->item->expects($this->any())->method('removeOption')->willReturn($this);

        $this->assertNull(
            $this->fXORateHelper->updateCartItems(
                [$this->item],
                $productRates,
                $itemUpdatedData,
                $quoteObjectItemsCount,
                $dbQuoteItemCount
            )
        );
    }

    /**
     * Test UpdateCartItems.
     *
     * @return void
     */
    public function testUpdateCartItemsWithToggleOfHCOOn()
    {
        $productRatesJson = json_encode($this->productRatesData());
        $productRates = json_decode($productRatesJson, true);
        $itemUpdatedData = [0 => [1, 2, 3, 4, 5, 6]];

        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('setDiscount')->with('0.00')->willReturnSelf();
        $this->item->expects($this->any())->method('setBaseRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setOriginalCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setIsSuperMode')->with(true)->willReturnSelf();
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getOptionId')->willReturn(2);
        $this->option->expects($this->any())->method('getOptionId')->willReturn('');
        $this->serializer->expects($this->any())->method('serialize')->willReturn('AnyString');
        $this->option->expects($this->any())->method('setvalue')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('save')->willReturn($this->quote);
        $this->item->expects($this->any())->method('getInstanceId')->willReturn(0);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId'=> 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));
        $quoteObjectItemsCount = 10;
        $dbQuoteItemCount = 11;
        $this->item->expects($this->any())->method('removeOption')->willReturn($this);
        $this->assertNull(
            $this->fXORateHelper->updateCartItems(
                [$this->item],
                $productRates,
                $itemUpdatedData,
                $quoteObjectItemsCount,
                $dbQuoteItemCount
            )
        );
    }

    /**
     * Test UpdateCartItems without Options.
     *
     * @return void
     */
    public function testUpdateCartItemsWithoutOptions()
    {
        $productRatesJson = json_encode($this->productRatesData());
        $productRates = json_decode($productRatesJson, true);
        $itemUpdatedData = [0 => [1, 2, 3, 4, 5, 6]];

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems', 'save'])
            ->getMock();
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('setDiscount')->with('0.00')->willReturnSelf();
        $this->item->expects($this->any())->method('setBaseRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setRowTotal')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setOriginalCustomPrice')->with("0.49")->willReturnSelf();
        $this->item->expects($this->any())->method('setIsSuperMode')->with(true)->willReturnSelf();
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->option->expects($this->any())->method('getOptionId')->willReturn('');
        $this->serializer->expects($this->any())->method('serialize')->willReturn('AnyString');
        $this->option->expects($this->any())->method('setvalue')->willReturn($quote);
        $quote->expects($this->any())->method('save')->willReturn($quote);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->optionInterface);
        $prodarray = [];
        $prodarray['external_prod'][0] = [
            'instanceId' => 0,
            'catalogReference' => 1,
            'preview_url' => 'url',
            'fxo_product' => 'product',
        ];

        $this->optionInterface
            ->expects($this->any())
            ->method('getValue')
            ->willReturn(json_encode($prodarray));

        $quoteObjectItemsCount = 11;
        $dbQuoteItemCount = 11;

        $this->item->expects($this->any())->method('removeOption')->willReturn($this);

        $this->assertNull(
            $this->fXORateHelper->updateCartItems(
                [$this->item],
                $productRates,
                $itemUpdatedData,
                $quoteObjectItemsCount,
                $dbQuoteItemCount
            )
        );
    }

    /**
     * Test CallRateAPI.
     *
     * @return void
     */
    public function testCallRateApi()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);

        $itemUpdatedData = [0 => [1, 2, 3, 4, 5, 6]];
        $couponCode = 'MGT001';
        $setupURL = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers = [
            "ABC",
        ];
        $dataString  =
            [
                'rateRequest' => [
                    'fedExAccountNumber' => null,
                    'profileAccountId' => null,
                    'site' => null,
                    'products' => [
                        0 => [
                            'productionContentAssociations' => [],
                            'userProductName' => 'Flyers',
                            'id' => '1463680545590',
                            'version' => 1,
                            'name' => 'Flyer',
                            'qty' => '50',
                            'priceable' => true,
                            'instanceId' => '0',
                            'proofRequired' => false,
                            'isOutSourced' => false,
                            'features' => [
                                0 => [
                                    'id' => '1448981549109',
                                    'name' => 'Paper Size',
                                    'choice' => [
                                        'id' => '1448986650332',
                                        'name' => '8.5x11',
                                        'properties' => [
                                            0 => [
                                                'id' => '1449069906033',
                                                'name' => 'MEDIA_HEIGHT',
                                                'value' => '11',
                                            ],
                                        ],
                                    ],
                                ],
                                1 => [
                                    'id' => '1448981549581',
                                    'name' => 'Print Color',
                                    'choice' => [
                                        'id' => '1448988600611',
                                        'name' => 'Full Color',
                                        'properties' => [
                                            0 => [
                                                'id' => '1453242778807',
                                                'name' => 'PRINT_COLOR',
                                                'value' => 'COLOR',
                                            ],
                                        ],
                                    ],
                                ],
                                2 => [
                                    'id' => '1448981549269',
                                    'name' => 'Sides',
                                    'choice' => [
                                        'id' => '1448988124560',
                                        'name' => 'Single-Sided',
                                        'properties' => [
                                            0 => [
                                                'id' => '1470166759236',
                                                'name' => 'SIDE_NAME',
                                                'value' => 'Single Sided',
                                            ],
                                            1 => [
                                                'id' => '1461774376168',
                                                'name' => 'SIDE',
                                                'value' => 'SINGLE',
                                            ],
                                        ],
                                    ],
                                ],
                                3 => [
                                    'id' => '1448984679218',
                                    'name' => 'Orientation',
                                    'choice' => [
                                        'id' => '1449000016327',
                                        'name' => 'Horizontal',
                                        'properties' => [
                                            0 => [
                                                'id' => '1453260266287',
                                                'name' => 'PAGE_ORIENTATION',
                                                'value' => 'LANDSCAPE',
                                            ],
                                        ],
                                    ],
                                ],
                                4 => [
                                    'id' => '1448981549741',
                                    'name' => 'Paper Type',
                                    'choice' => [
                                        'id' => '1448988664295',
                                        'name' => 'Laser(32 lb.)',
                                        'properties' => [
                                            0 => [
                                                'id' => '1450324098012',
                                                'name' => 'MEDIA_TYPE',
                                                'value' => 'E32',
                                            ],
                                            1 => [
                                                'id' => '1453234015081',
                                                'name' => 'PAPER_COLOR',
                                                'value' => '#FFFFFF',
                                            ],
                                            2 => [
                                                'id' => '1470166630346',
                                                'name' => 'MEDIA_NAME',
                                                'value' => '32lb',
                                            ],
                                            3 => [
                                                'id' => '1471275182312',
                                                'name' => 'MEDIA_CATEGORY',
                                                'value' => 'RESUME',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'pageExceptions' => [],
                            'contentAssociations' => [
                                0 => [
                                    'parentContentReference' => '12860750446056166911617735200641029290568',
                                    'contentReference' => '12860750448095622616502696333961844939287',
                                    'contentType' => 'IMAGE',
                                    'fileName' => 'nature1.jpeg',
                                    'contentReqId' => '1455709847200',
                                    'name' => 'Front_Side',
                                    'desc' => null,
                                    'purpose' => 'SINGLE_SHEET_FRONT',
                                    'specialInstructions' => '',
                                    'printReady' => true,
                                    'pageGroups' => [
                                        0 => [
                                            'start' => 1,
                                            'end' => 1,
                                            'width' => 11,
                                            'height' => 8.5,
                                            'orientation' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            'properties' => [
                                0  => [
                                    'id' => '1453242488328',
                                    'name' => 'ZOOM_PERCENTAGE',
                                    'value' => '50',
                                ],
                                1  => [
                                    'id' => '1453243262198',
                                    'name' => 'ENCODE_QUALITY',
                                    'value' => '100',
                                ],
                                2  => [
                                    'id' => '1453894861756',
                                    'name' => 'LOCK_CONTENT_ORIENTATION',
                                    'value' => false,
                                ],
                                3  => [
                                    'id' => '1453895478444',
                                    'name' => 'MIN_DPI',
                                    'value' => '150.0',
                                ],
                                4  => [
                                    'id' => '1454950109636',
                                    'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                    'value' => null,
                                ],
                                5  => [
                                    'id' => '1455050109636',
                                    'name' => 'DEFAULT_IMAGE_WIDTH',
                                    'value' => '8.5',
                                ],
                                6  => [
                                    'id' => '1455050109631',
                                    'name' => 'DEFAULT_IMAGE_HEIGHT',
                                    'value' => '11',
                                ],
                                7  => [
                                    'id' => '1464709502522',
                                    'name' => 'PRODUCT_QTY_SET',
                                    'value' => '50',
                                ],
                                8  => [
                                    'id' => '1459784717507',
                                    'name' => 'SKU',
                                    'value' => '40005',
                                ],
                                9  => [
                                    'id' => '1470151626854',
                                    'name' => 'SYSTEM_SI',
                                    'value' => 'ABC',
                                ],
                                10 => [
                                    'id' => '1494365340946',
                                    'name' => 'PREVIEW_TYPE',
                                    'value' => 'DYNAMIC',
                                ],
                                11 => [
                                    'id' => '1470151737965',
                                    'name' => 'TEMPLATE_AVAILABLE',
                                    'value' => 'YES',
                                ],
                                12 => [
                                    'id' => '1459784776049',
                                    'name' => 'PRICE',
                                    'value' => null,
                                ],
                                13 => [
                                    'id' => '1490292304798',
                                    'name' => 'MIGRATED_PRODUCT',
                                    'value' => 'true',
                                ],
                                14 => [
                                    'id' => '1558382273340',
                                    'name' => 'PNI_TEMPLATE',
                                    'value' => 'NO',
                                ],
                                15 => [
                                    'id' => '1602530744589',
                                    'name' => 'CONTROL_ID',
                                    'value' => '4',
                                ],
                            ],
                            'preview_url' => null,
                            'fxo_product' => null,
                        ],
                    ],
                    'recipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => '',
                            'attention' => null,
                            'pickUpDelivery' => [
                                'location' => ['id' => '1786'],
                                'requestedPickupLocalTime' => '',
                            ],
                            'productAssociations' => [
                                0 => [
                                    'id' => 0,
                                    'quantity' => 50,
                                ],
                            ],
                        ],
                    ],
                    'loyaltyCode' => null,
                    'specialInstructions' => null,
                    'coupons' => null,
                ],
            ];
        $rateApiOutputdata = json_encode(
            [
                'errors' => [
                    0 => [
                        'code' => "Codedata",
                        'message' => "messageData",
                    ],
                ],
                'output' => [
                    'rate' => [
                        'currency'    => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines'  => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId'  => '1508784838900',
                                        'retailPrice' => '$0.49',
                                        'discountAmount' => '$0.00',
                                        'unitQuantity' => 1,
                                        'linePrice' => '$0.49',
                                        'priceable' => 1,
                                        'productLineDiscounts' => [[
                                            "type"=> "AR_CUSTOMERS",
                                            "amount"=> "($52.50)"
                                        ], [
                                            "type"=> "QUANTITY",
                                            "amount"=> "($250.00)"
                                        ]],
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => 'Single Sided Color',
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => '$0.49',
                                                'detailDiscountPrice' => '$0.00',
                                                'detailUnitPrice' => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => '(2.00)']
                                    ],
                                    'deliveryRetailPrice' => '$0.49',
                                ]
                                ],
                                'grossAmount' => '$0.49',
                                'discounts' => [[
                                    "type"=> "AR_CUSTOMERS",
                                    "amount"=> "($52.50)"
                                ], [
                                    "type"=> "QUANTITY",
                                    "amount"=> "($250.00)"
                                ], [
                                    "type"=> "COUPON",
                                    "amount"=> "($16.00)"
                                ]
                                ],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount' => '$0.49',
                                'taxableAmount' => '$0.49',
                                'taxAmount' => '$0.00',
                                'totalAmount' => '$0.49',
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],
                    ],
                    'alerts' => [
                        '0' => ['code' => 'MAX.PRODUCT.COUNT']
                    ],
                ],
            ]
        );
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->logger->expects($this->any())->method('info')->willReturnSelf();
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'coupon',
                false
            )
        );
    }

    /**
     * Test CallRateAPI.
     *
     * @return void
     */
    public function testCallRateApiShipping()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromShipping')->willReturn(true);
        $this->quote->expects($this->any())->method('getIsFromPickup')->willReturn(true);

        $itemUpdatedData = [
            1,
            2,
            3,
            4,
            5,
            6,
        ];
        $couponCode = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers           = [
            "ABC",
        ];
        $dataString = [
            'rateRequest' => [
                'fedExAccountNumber' => null,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty'  => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id'  => '1448988124560',
                                    'name'  => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name'  => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name'  => 'Orientation',
                                'choice' => [
                                    'id'  => '1449000016327',
                                    'name'  => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id'  => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0 => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $rateApiOutputdata = json_encode(
            [
                'errors' => [
                    0 => [
                        'code' => "Codedata",
                        'message' => "messageData",
                    ],
                ],
                'output' => [
                    'rate' => [
                        'currency'    => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines'        => [
                                    0 => [
                                        'instanceId'            => 0,
                                        'productId'             => '1508784838900',
                                        'retailPrice'           => '$0.49',
                                        'discountAmount'        => '$0.00',
                                        'unitQuantity'          => 1,
                                        'linePrice'             => '$0.49',
                                        'priceable'             => 1,
                                        'productLineDetails'    => [
                                            0 => [
                                                'detailCode'                => '0173',
                                                'description'               => 'Single Sided Color',
                                                'detailCategory'            => 'PRINTING',
                                                'unitQuantity'              => 1,
                                                'unitOfMeasurement'         => 'EACH',
                                                'detailPrice'               => '$0.49',
                                                'detailDiscountPrice'       => '$0.00',
                                                'detailUnitPrice'           => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice'    => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice'      => '0.49',
                                        'editable'              => '',
                                    ],
                                ],
                                'grossAmount'         => '$0.49',
                                'discounts'           => [ [
                                    "type"=> "QUANTITY",
                                    "amount"=> "($250.00)"
                                ],[
                                    "type"=> "COUPON",
                                    "amount"=> "($16.00)"
                                ]],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount'           => '$0.49',
                                'taxableAmount'       => '$0.49',
                                'taxAmount'           => '$0.00',
                                'totalAmount'         => '$0.49',
                                'estimatedVsActual'   => 'ACTUAL',
                            ],
                        ],
                    ],
                    'alerts' => [
                        '0' => ['code' => 'MAX.PRODUCT.COUNT']
                    ],
                ],
            ]
        );
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);

        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->cart,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'coupon',
                false
            )
        );
    }

    /**
     * Test CallRateAPI for reorder
     *
     * @return void
     */
    public function testCallRateApiForReorder()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);

        $itemUpdatedData = [0 => [1, 2, 3, 4, 5, 6]];
        $couponCode = 'MGT001';
        $setupURL = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers = [
            "ABC",
        ];
        $dataString = [
            'rateRequest' => [
                'fedExAccountNumber' => null,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id'  => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0  => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1  => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2  => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3  => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4  => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5  => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6  => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $rateApiOutputdata = json_encode(
            [
                'errors' => [
                    0 => [
                        'code' => "Codedata",
                        'message' => "messageData",
                    ],
                ],
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => '1508784838900',
                                        'retailPrice' => '$0.49',
                                        'discountAmount' => '$0.00',
                                        'unitQuantity' => 1,
                                        'linePrice' => '$0.49',
                                        'priceable' => 1,
                                        'productLineDiscounts' => [[
                                            "type"=> "AR_CUSTOMERS",
                                            "amount"=> "($52.50)"
                                        ], [
                                            "type"=> "QUANTITY",
                                            "amount"=> "($250.00)"
                                        ]],
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => 'Single Sided Color',
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => '$0.49',
                                                'detailDiscountPrice' => '$0.00',
                                                'detailUnitPrice' => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => '$0.49',
                                'discounts' => [ [
                                    "type"=> "QUANTITY",
                                    "amount"=> "($250.00)"
                                ], [
                                    "type"=> "COUPON",
                                    "amount"=> "($16.00)"
                                ]],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount' => '$0.49',
                                'taxableAmount' => '$0.49',
                                'taxAmount' => '$0.00',
                                'totalAmount' => '$0.49',
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],
                    ],
                    'alerts' => [
                        '0' => ['code' => 'MAX.PRODUCT.COUNT']
                    ],
                ],
            ]
        );
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'reorder',
                false
            )
        );
    }

    /**
     * Test CallRateAPI With FedEx Account Number.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testCallRateApiWithFedExAccount()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);

        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode = 'MGT001';
        $setupURL = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';
        $headers = [
            "ABC",
        ];
        $dataString = [
            'rateRequest' => [
                'fedExAccountNumber'  => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id'  => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id' => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0 => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $rateApiOutputdata = json_encode(
            [
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => '1508784838900',
                                        'retailPrice' => '$0.49',
                                        'discountAmount' => '$0.00',
                                        'unitQuantity' => 1,
                                        'linePrice' => '$0.49',
                                        'priceable' => 1,
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => 'Single Sided Color',
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => '$0.49',
                                                'detailDiscountPrice' => '$0.00',
                                                'detailUnitPrice' => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => '$0.49',
                                'discounts' => [ [
                                    "type"=> "QUANTITY",
                                    "amount"=> "($250.00)"
                                ], [
                                    "type"=> "COUPON",
                                    "amount"=> "($16.00)"
                                ]],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount' => '$0.49',
                                'taxableAmount' => '$0.49',
                                'taxAmount' => '$0.00',
                                'totalAmount' => '$0.49',
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->assertNotNull($this->fXORateHelper->callRateApi(
            $this->quote,
            [$this->item],
            $itemUpdatedData,
            $couponCode,
            $setupURL,
            json_encode($headers),
            json_encode($dataString),
            1,
            1,
            'coupon',
            false
        ));
    }

    /**
     * Test CallRateAPI With Error Output.
     *
     * @return void
     */
    public function testCallRateApiWithError()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode = 'MGT001';
        $setupURL = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString = [
            'rateRequest' => [
                'fedExAccountNumber'  => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty'  => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id'  => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id' => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end'  => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0 => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $output = [
            'output' => [
                'alerts' => [
                    '0' => [
                        'code' => 'ABC',
                        'message' => 'Error'
                    ],
                ],
            ],
        ];
        $rateApiOutputdata = json_encode($output);

        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                false
            )
        );
    }

    /**
     * Test CallRateAPI With Error Output.
     *
     * @return void
     */
    public function testCallRateApiWithErrorFxoPromoCodePlacementOn()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode      = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers           = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString        = [
            'rateRequest' => [
                'fedExAccountNumber' => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id'  => '1448988600611',
                                    'name'  => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name'  => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id'  => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id'  => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0 => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9  => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $output = [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDiscounts' => [[
                                        "type"=> "AR_CUSTOMERS",
                                        "amount"=> "($52.50)"
                                    ], [
                                        "type"=> "QUANTITY",
                                        "amount"=> "($250.00)"
                                    ]],
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'AR_CUSTOMERS',
                                ],
                            ],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                        ],
                    ],
                ],
                'alerts' => [
                    '0' => [
                        'code' => 'ABC',
                        'message' => 'Error'
                    ],
                ],
            ],
        ];
        $rateApiOutputdata = json_encode($output);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('setSubtotal')->willReturnSelf();
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                false
            )
        );
    }

    /**
     * Test CallRateAPI With Error Output.
     *
     * @return void
     */
    public function testCallRateApiFxoPromoCodePlacementOnToggle()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode      = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';
        $headers           = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString =
            [
                'rateRequest' => [
                    'fedExAccountNumber' => 12345678,
                    'profileAccountId' => null,
                    'site' => null,
                    'products' => [
                        0 => [
                            'productionContentAssociations' => [],
                            'userProductName' => 'Flyers',
                            'id' => '1463680545590',
                            'version' => 1,
                            'name' => 'Flyer',
                            'qty' => '50',
                            'priceable' => true,
                            'instanceId' => '0',
                            'proofRequired' => false,
                            'isOutSourced' => false,
                            'features' => [
                                0 => [
                                    'id' => '1448981549109',
                                    'name' => 'Paper Size',
                                    'choice' => [
                                        'id' => '1448986650332',
                                        'name' => '8.5x11',
                                        'properties' => [
                                            0 => [
                                                'id' => '1449069906033',
                                                'name' => 'MEDIA_HEIGHT',
                                                'value' => '11',
                                            ],
                                        ],
                                    ],
                                ],
                                1 => [
                                    'id' => '1448981549581',
                                    'name' => 'Print Color',
                                    'choice' => [
                                        'id' => '1448988600611',
                                        'name' => 'Full Color',
                                        'properties' => [
                                            0 => [
                                                'id' => '1453242778807',
                                                'name' => 'PRINT_COLOR',
                                                'value' => 'COLOR',
                                            ],
                                        ],
                                    ],
                                ],
                                2 => [
                                    'id' => '1448981549269',
                                    'name' => 'Sides',
                                    'choice' => [
                                        'id' => '1448988124560',
                                        'name' => 'Single-Sided',
                                        'properties' => [
                                            0 => [
                                                'id' => '1470166759236',
                                                'name' => 'SIDE_NAME',
                                                'value' => 'Single Sided',
                                            ],
                                            1 => [
                                                'id' => '1461774376168',
                                                'name' => 'SIDE',
                                                'value' => 'SINGLE',
                                            ],
                                        ],
                                    ],
                                ],
                                3 => [
                                    'id' => '1448984679218',
                                    'name' => 'Orientation',
                                    'choice' => [
                                        'id' => '1449000016327',
                                        'name' => 'Horizontal',
                                        'properties' => [
                                            0 => [
                                                'id' => '1453260266287',
                                                'name' => 'PAGE_ORIENTATION',
                                                'value' => 'LANDSCAPE',
                                            ],
                                        ],
                                    ],
                                ],
                                4 => [
                                    'id' => '1448981549741',
                                    'name' => 'Paper Type',
                                    'choice' => [
                                        'id' => '1448988664295',
                                        'name' => 'Laser(32 lb.)',
                                        'properties' => [
                                            0 => [
                                                'id' => '1450324098012',
                                                'name' => 'MEDIA_TYPE',
                                                'value' => 'E32',
                                            ],
                                            1 => [
                                                'id' => '1453234015081',
                                                'name' => 'PAPER_COLOR',
                                                'value' => '#FFFFFF',
                                            ],
                                            2 => [
                                                'id' => '1470166630346',
                                                'name' => 'MEDIA_NAME',
                                                'value' => '32lb',
                                            ],
                                            3 => [
                                                'id' => '1471275182312',
                                                'name' => 'MEDIA_CATEGORY',
                                                'value' => 'RESUME',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'pageExceptions' => [],
                            'contentAssociations' => [
                                0 => [
                                    'parentContentReference' => '12860750446056166911617735200641029290568',
                                    'contentReference' => '12860750448095622616502696333961844939287',
                                    'contentType' => 'IMAGE',
                                    'fileName' => 'nature1.jpeg',
                                    'contentReqId' => '1455709847200',
                                    'name' => 'Front_Side',
                                    'desc' => null,
                                    'purpose' => 'SINGLE_SHEET_FRONT',
                                    'specialInstructions' => '',
                                    'printReady' => true,
                                    'pageGroups' => [
                                        0 => [
                                            'start' => 1,
                                            'end' => 1,
                                            'width' => 11,
                                            'height' => 8.5,
                                            'orientation' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            'properties' => [
                                0 => [
                                    'id' => '1453242488328',
                                    'name' => 'ZOOM_PERCENTAGE',
                                    'value' => '50',
                                ],
                                1 => [
                                    'id' => '1453243262198',
                                    'name' => 'ENCODE_QUALITY',
                                    'value' => '100',
                                ],
                                2 => [
                                    'id' => '1453894861756',
                                    'name' => 'LOCK_CONTENT_ORIENTATION',
                                    'value' => false,
                                ],
                                3 => [
                                    'id' => '1453895478444',
                                    'name' => 'MIN_DPI',
                                    'value' => '150.0',
                                ],
                                4 => [
                                    'id' => '1454950109636',
                                    'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                    'value' => null,
                                ],
                                5 => [
                                    'id' => '1455050109636',
                                    'name' => 'DEFAULT_IMAGE_WIDTH',
                                    'value' => '8.5',
                                ],
                                6 => [
                                    'id' => '1455050109631',
                                    'name' => 'DEFAULT_IMAGE_HEIGHT',
                                    'value' => '11',
                                ],
                                7 => [
                                    'id' => '1464709502522',
                                    'name' => 'PRODUCT_QTY_SET',
                                    'value' => '50',
                                ],
                                8 => [
                                    'id' => '1459784717507',
                                    'name' => 'SKU',
                                    'value' => '40005',
                                ],
                                9  => [
                                    'id' => '1470151626854',
                                    'name' => 'SYSTEM_SI',
                                    'value' => 'ABC',
                                ],
                                10 => [
                                    'id' => '1494365340946',
                                    'name' => 'PREVIEW_TYPE',
                                    'value' => 'DYNAMIC',
                                ],
                                11 => [
                                    'id' => '1470151737965',
                                    'name' => 'TEMPLATE_AVAILABLE',
                                    'value' => 'YES',
                                ],
                                12 => [
                                    'id' => '1459784776049',
                                    'name' => 'PRICE',
                                    'value' => null,
                                ],
                                13 => [
                                    'id' => '1490292304798',
                                    'name' => 'MIGRATED_PRODUCT',
                                    'value' => 'true',
                                ],
                                14 => [
                                    'id' => '1558382273340',
                                    'name' => 'PNI_TEMPLATE',
                                    'value' => 'NO',
                                ],
                                15 => [
                                    'id' => '1602530744589',
                                    'name' => 'CONTROL_ID',
                                    'value' => '4',
                                ],
                            ],
                            'preview_url' => null,
                            'fxo_product' => null,
                        ],
                    ],
                    'recipients' => [
                        0 => [
                            'contact' => null,
                            'reference' => '',
                            'attention' => null,
                            'pickUpDelivery' => [
                                'location' => ['id' => '1786'],
                                'requestedPickupLocalTime' => '',
                            ],
                            'productAssociations' => [
                                0 => [
                                    'id' => 0,
                                    'quantity' => 50,
                                ],
                            ],
                        ],
                    ],
                    'loyaltyCode' => null,
                    'specialInstructions' => null,
                    'coupons' => null,
                ],
            ];
        $output =
            [
                'output' => [
                    'rate' => [
                        'currency' => 'USD',
                        'rateDetails' => [
                            0 => [
                                'productLines' => [
                                    0 => [
                                        'instanceId' => 0,
                                        'productId' => '1508784838900',
                                        'retailPrice' => '$0.49',
                                        'discountAmount' => '$0.00',
                                        'unitQuantity' => 1,
                                        'linePrice' => '$0.49',
                                        'priceable' => 1,
                                        'productLineDiscounts' => [[
                                            "type"=> "AR_CUSTOMERS",
                                            "amount"=> "($52.50)"
                                        ], [
                                            "type"=> "QUANTITY",
                                            "amount"=> "($250.00)"
                                        ]],
                                        'productLineDetails' => [
                                            0 => [
                                                'detailCode' => '0173',
                                                'description' => 'Single Sided Color',
                                                'detailCategory' => 'PRINTING',
                                                'unitQuantity' => 1,
                                                'unitOfMeasurement' => 'EACH',
                                                'detailPrice' => '$0.49',
                                                'detailDiscountPrice' => '$0.00',
                                                'detailUnitPrice' => '$0.4900',
                                                'detailDiscountedUnitPrice' => '$0.00',
                                            ],
                                        ],
                                        'productRetailPrice' => 0.49,
                                        'productDiscountAmount' => '0.00',
                                        'productLinePrice' => '0.49',
                                        'editable' => '',
                                    ],
                                ],
                                'grossAmount' => '$0.49',
                                'discounts' => [
                                    0 => [
                                        'amount' => '($0.05)',
                                        'type' => 'AR_CUSTOMERS',
                                    ],
                                ],
                                'totalDiscountAmount' => '$0.00',
                                'netAmount' => '$0.49',
                                'taxableAmount' => '$0.49',
                                'taxAmount' => '$0.00',
                                'totalAmount' => '$0.49',
                                'estimatedVsActual' => 'ACTUAL',
                            ],
                        ],
                    ],
                    'alerts' => [
                        '0' => [
                            'code' => 'ABC',
                            'message' => 'Error'
                        ],
                    ],
                ],
            ];
        $rateApiOutputdata = json_encode($output);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('setSubtotal')->willReturnSelf();
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                false
            )
        );
    }//end testCallRateApiWithError()

    /**
     * Test CallRateAPI With Error Output.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function testCallRateApiWithErrorFxoPromoCodePlacementOnAndDiscountCoupon()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode      = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';
        $headers           = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString = [
            'rateRequest' => [
                'fedExAccountNumber'  => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id'  => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name' => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose'=> 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties' => [
                            0 => [
                                'id' => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name' => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name' => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name' => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $output = [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDiscounts' => [[
                                        "type"=> "COUPON",
                                        "amount"=> "($52.50)"
                                    ]
                                    ],
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                        ],
                    ],
                ],
                'alerts' => [
                    '0' => [
                        'code' => 'ABC',
                        'message' => 'Error'
                    ],
                ],
            ],
        ];
        $rateApiOutputdata = json_encode($output);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getShippingAddress')->willReturnSelf();
        $this->quote->expects($this->any())->method('setSubtotal')->willReturnSelf();
        $this->quote->expects($this->any())->method('setPromoDiscount')->willReturnSelf();

        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                false
            )
        );
    }

    /**
     * Test CallRateAPI With Error Output.
     *
     * @return void
     */
    public function testCallRateApiWithCode()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode      = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers           = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString        = [
            'rateRequest' => [
                'fedExAccountNumber' => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id' => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name'  => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties'  => [
                            0 => [
                                'id'  => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name'  => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name'  => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name'  => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $output = [
            'output' => [
                'alerts' => [
                    '0' => ['message' => 'Error'],
                ],
            ],
        ];
        $rateApiOutputdata = json_encode($output);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                false
            )
        );
    }

    /**
     * Test testCallRateApiForRM With Output.
     *
     * @return void
     */
    public function testCallRateApiForRM()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        // Fetch Items.
        $this->quote->expects($this->any())->method('getAllItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $itemUpdatedData = [1, 2, 3, 4, 5, 6];
        $couponCode      = 'MGT001';
        $setupURL        = 'https://apitest.fedex.com/rate/fedexoffice/v2/rates';

        $headers           = [
            "Content-Type: application\/json",
            "Accept: application\/json",
            "Accept-Language: json",
            "Content-Length: 3371",
            "Authorization: Bearer l7xx1cb26690fadd4b2789e0888a96b80ee2",
            "Cookie: ABC",
        ];
        $dataString        = [
            'rateRequest' => [
                'fedExAccountNumber' => 12345678,
                'profileAccountId' => null,
                'site' => null,
                'products' => [
                    0 => [
                        'productionContentAssociations' => [],
                        'userProductName' => 'Flyers',
                        'id' => '1463680545590',
                        'version' => 1,
                        'name' => 'Flyer',
                        'qty' => '50',
                        'priceable' => true,
                        'instanceId' => '0',
                        'proofRequired' => false,
                        'isOutSourced' => false,
                        'features' => [
                            0 => [
                                'id' => '1448981549109',
                                'name' => 'Paper Size',
                                'choice' => [
                                    'id' => '1448986650332',
                                    'name' => '8.5x11',
                                    'properties' => [
                                        0 => [
                                            'id' => '1449069906033',
                                            'name' => 'MEDIA_HEIGHT',
                                            'value' => '11',
                                        ],
                                    ],
                                ],
                            ],
                            1 => [
                                'id' => '1448981549581',
                                'name' => 'Print Color',
                                'choice' => [
                                    'id' => '1448988600611',
                                    'name' => 'Full Color',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453242778807',
                                            'name' => 'PRINT_COLOR',
                                            'value' => 'COLOR',
                                        ],
                                    ],
                                ],
                            ],
                            2 => [
                                'id' => '1448981549269',
                                'name' => 'Sides',
                                'choice' => [
                                    'id' => '1448988124560',
                                    'name' => 'Single-Sided',
                                    'properties' => [
                                        0 => [
                                            'id' => '1470166759236',
                                            'name' => 'SIDE_NAME',
                                            'value' => 'Single Sided',
                                        ],
                                        1 => [
                                            'id' => '1461774376168',
                                            'name' => 'SIDE',
                                            'value' => 'SINGLE',
                                        ],
                                    ],
                                ],
                            ],
                            3 => [
                                'id' => '1448984679218',
                                'name' => 'Orientation',
                                'choice' => [
                                    'id' => '1449000016327',
                                    'name' => 'Horizontal',
                                    'properties' => [
                                        0 => [
                                            'id' => '1453260266287',
                                            'name' => 'PAGE_ORIENTATION',
                                            'value' => 'LANDSCAPE',
                                        ],
                                    ],
                                ],
                            ],
                            4 => [
                                'id' => '1448981549741',
                                'name' => 'Paper Type',
                                'choice' => [
                                    'id' => '1448988664295',
                                    'name' => 'Laser(32 lb.)',
                                    'properties' => [
                                        0 => [
                                            'id' => '1450324098012',
                                            'name' => 'MEDIA_TYPE',
                                            'value' => 'E32',
                                        ],
                                        1 => [
                                            'id' => '1453234015081',
                                            'name' => 'PAPER_COLOR',
                                            'value' => '#FFFFFF',
                                        ],
                                        2 => [
                                            'id' => '1470166630346',
                                            'name'  => 'MEDIA_NAME',
                                            'value' => '32lb',
                                        ],
                                        3 => [
                                            'id' => '1471275182312',
                                            'name' => 'MEDIA_CATEGORY',
                                            'value' => 'RESUME',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'pageExceptions' => [],
                        'contentAssociations' => [
                            0 => [
                                'parentContentReference' => '12860750446056166911617735200641029290568',
                                'contentReference' => '12860750448095622616502696333961844939287',
                                'contentType' => 'IMAGE',
                                'fileName' => 'nature1.jpeg',
                                'contentReqId' => '1455709847200',
                                'name' => 'Front_Side',
                                'desc' => null,
                                'purpose' => 'SINGLE_SHEET_FRONT',
                                'specialInstructions' => '',
                                'printReady' => true,
                                'pageGroups' => [
                                    0 => [
                                        'start' => 1,
                                        'end' => 1,
                                        'width' => 11,
                                        'height' => 8.5,
                                        'orientation' => 'LANDSCAPE',
                                    ],
                                ],
                            ],
                        ],
                        'properties'  => [
                            0 => [
                                'id'  => '1453242488328',
                                'name' => 'ZOOM_PERCENTAGE',
                                'value' => '50',
                            ],
                            1 => [
                                'id' => '1453243262198',
                                'name' => 'ENCODE_QUALITY',
                                'value' => '100',
                            ],
                            2 => [
                                'id' => '1453894861756',
                                'name' => 'LOCK_CONTENT_ORIENTATION',
                                'value' => false,
                            ],
                            3 => [
                                'id' => '1453895478444',
                                'name' => 'MIN_DPI',
                                'value' => '150.0',
                            ],
                            4 => [
                                'id' => '1454950109636',
                                'name' => 'USER_SPECIAL_INSTRUCTIONS',
                                'value' => null,
                            ],
                            5 => [
                                'id' => '1455050109636',
                                'name' => 'DEFAULT_IMAGE_WIDTH',
                                'value' => '8.5',
                            ],
                            6 => [
                                'id' => '1455050109631',
                                'name'  => 'DEFAULT_IMAGE_HEIGHT',
                                'value' => '11',
                            ],
                            7 => [
                                'id' => '1464709502522',
                                'name' => 'PRODUCT_QTY_SET',
                                'value' => '50',
                            ],
                            8 => [
                                'id' => '1459784717507',
                                'name' => 'SKU',
                                'value' => '40005',
                            ],
                            9 => [
                                'id' => '1470151626854',
                                'name' => 'SYSTEM_SI',
                                'value' => 'ABC',
                            ],
                            10 => [
                                'id' => '1494365340946',
                                'name' => 'PREVIEW_TYPE',
                                'value' => 'DYNAMIC',
                            ],
                            11 => [
                                'id' => '1470151737965',
                                'name' => 'TEMPLATE_AVAILABLE',
                                'value' => 'YES',
                            ],
                            12 => [
                                'id' => '1459784776049',
                                'name'  => 'PRICE',
                                'value' => null,
                            ],
                            13 => [
                                'id' => '1490292304798',
                                'name' => 'MIGRATED_PRODUCT',
                                'value' => 'true',
                            ],
                            14 => [
                                'id' => '1558382273340',
                                'name'  => 'PNI_TEMPLATE',
                                'value' => 'NO',
                            ],
                            15 => [
                                'id' => '1602530744589',
                                'name' => 'CONTROL_ID',
                                'value' => '4',
                            ],
                        ],
                        'preview_url' => null,
                        'fxo_product' => null,
                    ],
                ],
                'recipients' => [
                    0 => [
                        'contact' => null,
                        'reference' => '',
                        'attention' => null,
                        'pickUpDelivery' => [
                            'location' => ['id' => '1786'],
                            'requestedPickupLocalTime' => '',
                        ],
                        'productAssociations' => [
                            0 => [
                                'id' => 0,
                                'quantity' => 50,
                            ],
                        ],
                    ],
                ],
                'loyaltyCode' => null,
                'specialInstructions' => null,
                'coupons' => null,
            ],
        ];
        $output = [
            'output' => [
                'alerts' => [
                    '0' => ['message' => 'Error'],
                ],
            ],
        ];
        $rateApiOutputdata = json_encode($output);
        $this->curl->expects($this->any())->method('getBody')->willReturn($rateApiOutputdata);
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        // Fetch Quote.
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->assertNotNull(
            $this->fXORateHelper->callRateApi(
                $this->quote,
                [$this->item],
                $itemUpdatedData,
                $couponCode,
                $setupURL,
                json_encode($headers),
                json_encode($dataString),
                1,
                1,
                'account',
                true
            )
        );
    }

    /**
     * Test GetFXORate.
     *
     * @return void
     */
    public function testGetFXORate()
    {
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type' => 'Cookie',
                    ]
                )
            );
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->checkoutSessionMock->expects($this->any())->method('getData')
            ->with('remove_fedex_account_number')->willReturn(false);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['fedex_account_number'],
            ['fedex_account_number'],
            ['coupon_code'],
        )->willReturnOnConsecutiveCalls(null, null, null);
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn(12345678);
        $this->quote->expects($this->any())->method('setData');

        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getMiraklOfferId')->willReturn('Test');
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())
            ->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,
            "name":"Posters","qty":1,"priceable":true,
            "instanceId":1632939962051}]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));
        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate.
     *
     * @return void
     */
    public function testGetFXORateIterateItems()
    {
        $this->quote->expects($this->any())->method('setData');
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(0);
        $this->productModel->expects($this->any())
            ->method('load')->with(0)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(0);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,
            "name":"Posters","qty":1,"priceable":true,
            "instanceId":1632939962051}]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->data->expects($this->any())->method('getAuthHeaderValue')->willReturn("Test");
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));
        $this->customerSession->expects($this->any())->method('unsValidateContentApiExpired')->willReturn(true);
        $this->customerSession->expects($this->any())->method('getExpiredItemIds')->willReturn(['1234', '1235']);
        $this->customerSession->expects($this->any())->method('setValidateContentApiExpired')->willReturn(true);

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    public function testGetFXORateWithEmptyQuoteObjectItemsCount()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);

        $result = $this->fXORateHelper->getFXORate($this->quote);

        $this->assertNull($result, 'Expected null when quote object items count is empty.');
    }

    /**
     *  Test GetFXORate.
     * @return void
     */
    public function testIterateItemsWithInstanceIdFeatureToggleActive()
    {
        $this->quote->expects($this->any())->method('setData');
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(0);
        $this->productModel->expects($this->any())
            ->method('load')->with(0)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(0);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[
        {"userProductName":"Poster Prints",
        "id":"1466693799380",
        "version":2,"name":
        "Posters",
        "qty":1,
        "priceable":true,
        "instanceId":1632939962051
        }
        ]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['armada_call_rate_api_shipping_validation'],
                ['tech_titans_D_174940_documents_cannot_be_added_to_the_cart'],
                ['explorers_d196313_fix']
            )
            ->willReturnOnConsecutiveCalls(false, true, true);
        $this->cartDataHelper->expects($this->any())->method('getDltThresholdHours')->willReturn(12345678);
        $this->cartDataHelper->expects($this->any())->method('setDltThresholdHours')->willReturn($decodedData);
        $this->config->expects($this->any())->method('isRateQuoteProductAssociationEnabled')->willReturn(true);
        $result = $this->fXORateHelper->iterateItems([$this->item], null, 1);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('rateApiProdRequestData', $result);
        $this->assertArrayHasKey('itemsUpdatedData', $result);
        $this->assertEquals(1, count($result['rateApiProdRequestData']));
        $this->assertEquals(1, count($result['itemsUpdatedData']));
        $this->assertEquals('1466693799380', $result['rateApiProdRequestData'][0]['id']);
    }

    /**
     *  Test GetFXORate.
     * @return void
     */
    public function testIterateItemsWithInstanceIdFeatureToggleInActive()
    {
        $this->quote->expects($this->any())->method('setData');
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(0);
        $this->productModel->expects($this->any())
            ->method('load')->with(0)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(0);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod": [
        {"userProductName":"Poster Prints",
        "id":"1466693799380",
        "version":2,
        "name":"Posters",
        "qty":1,
        "priceable":true,
        "instanceId":1632939962051
        }]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['armada_call_rate_api_shipping_validation'],
                ['tech_titans_D_174940_documents_cannot_be_added_to_the_cart'],
                ['explorers_d196313_fix']
            )
            ->willReturnOnConsecutiveCalls(false, false, true);
        $this->cartDataHelper->expects($this->any())->method('getDltThresholdHours')->willReturn(12345678);
        $this->cartDataHelper->expects($this->any())->method('setDltThresholdHours')->willReturn($decodedData);
        $this->config->expects($this->any())->method('isRateQuoteProductAssociationEnabled')->willReturn(true);
        $result = $this->fXORateHelper->iterateItems([$this->item], 1, 12);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('rateApiProdRequestData', $result);
        $this->assertArrayHasKey('itemsUpdatedData', $result);
        $this->assertEquals(1, count($result['rateApiProdRequestData']));
        $this->assertEquals(1, count($result['itemsUpdatedData']));
        $this->assertEquals('1466693799380', $result['rateApiProdRequestData'][0]['id']);
    }

    /**
     *  Test GetFXORate.
     * @return void
     */
    public function testIterateItemsWithDBAndQuoteNotEqual()
    {
        $this->quote->expects($this->any())->method('setData');
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(0);
        $this->productModel->expects($this->any())
            ->method('load')->with(0)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(0);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":
        [
        {"userProductName":"Poster Prints",
        "id":"1466693799380",
        "version":2,
        "name":"Posters",
        "qty":1,
        "priceable":true,
        "instanceId":1632939962051}
        ]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['armada_call_rate_api_shipping_validation'],
                ['tech_titans_D_174940_documents_cannot_be_added_to_the_cart'],
                ['explorers_d196313_fix']
            )
            ->willReturnOnConsecutiveCalls(false, false, true);
        $this->cartDataHelper->expects($this->any())->method('getDltThresholdHours')->willReturn(12345678);
        $this->cartDataHelper->expects($this->any())->method('setDltThresholdHours')->willReturn($decodedData);
        $this->config->expects($this->any())->method('isRateQuoteProductAssociationEnabled')->willReturn(true);
        $result = $this->fXORateHelper->iterateItems([$this->item], 1, 12);
        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('rateApiProdRequestData', $result);
        $this->assertArrayHasKey('itemsUpdatedData', $result);
        $this->assertEquals(1, count($result['rateApiProdRequestData']));
        $this->assertEquals(1, count($result['itemsUpdatedData']));
        $this->assertEquals('1466693799380', $result['rateApiProdRequestData'][0]['id']);
    }

    /**
     * Test GetFXORate.
     *
     * @return void
     */
    public function testGetFXORateIterateItemsForNonFxoProduct()
    {
        $this->quote->expects($this->any())->method('setData');
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(0);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(0);
        $this->productModel->expects($this->any())
            ->method('load')->with(0)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(0);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())->method('getAttributeSetName')
            ->willReturn('printOnDemand');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,
            "name":"Posters","qty":1,"priceable":true,
            "instanceId":1632939962051}]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate.
     *
     * @return void
     */
    public function testGetFXORateSystemErrorToggle()
    {
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('isCommercialCustomer')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')
            ->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(1);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())->method('getData')
            ->with('remove_fedex_account_number')->willReturn(false);
        $this->quote->expects($this->any())->method('getData')->withConsecutive(
            ['fedex_account_number'],
            ['fedex_account_number'],
            ['coupon_code'],
        )->willReturnOnConsecutiveCalls(null, null, null);
        $this->cartDataHelper->expects($this->any())->method('encryptData')->willReturn(12345678);
        $this->quote->expects($this->any())->method('setData');

        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(1);
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->companyHelper->expects($this->any())->method('getCustomerCompany')->willReturn($this->companyMock);
        $this->companyMock->expects($this->any())->method('getCompanyName')->willReturn('walmart');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())
            ->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,
            "name":"Posters","qty":1,"priceable":true,
            "instanceId":1632939962051}]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    public function testGetFXORateWithHcoToggle()
    {
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturn(['fedexaccountnumber']);
        $this->companyHelper->expects($this->any())->method('getFXOAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->quote->expects($this->any())->method('getData')->willReturn('XYZ');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())
            ->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,
            "name":"Posters","qty":1,"priceable":true,
            "instanceId":1632939962051}]}';
        $decodedData  = [
            'external_prod' => [
                [
                    'userProductName' => 'Poster Prints',
                    'id' => 1466693799380,
                    'version' => 2,
                    'name' => 'Posters',
                    'qty' => 1,
                    'priceable' => 1,
                    'instanceId' => 1632939962051,
                ],
            ],
        ];
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($decodedData);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test reset cart discount
     *
     * @return void
     */
    public function testResetCartDiscounts()
    {

        $productRates = [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [
                                0 => [
                                    'amount' => '($0.05)',
                                    'type' => 'COUPON',
                                ],
                            ],
                        ],
                    ],

                ],
            ],
        ];

        $this->assertNull($this->fXORateHelper->resetCartDiscounts($this->quote, $productRates));
    }

    /**
     * Test reset cart discount
     *
     * @return void
     */
    public function testResetCartDiscountsWithToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);
        $productRates = [
            'output' => [
                'rate' => [
                    'currency' => 'USD',
                    'rateDetails' => [
                        0 => [
                            'productLines' => [
                                0 => [
                                    'instanceId' => 0,
                                    'productId' => '1508784838900',
                                    'retailPrice' => '$0.49',
                                    'discountAmount' => '$0.00',
                                    'unitQuantity' => 1,
                                    'linePrice' => '$0.49',
                                    'priceable' => 1,
                                    'productLineDetails' => [
                                        0 => [
                                            'detailCode' => '0173',
                                            'description' => 'Single Sided Color',
                                            'detailCategory' => 'PRINTING',
                                            'unitQuantity' => 1,
                                            'unitOfMeasurement' => 'EACH',
                                            'detailPrice' => '$0.49',
                                            'detailDiscountPrice' => '$0.00',
                                            'detailUnitPrice' => '$0.4900',
                                            'detailDiscountedUnitPrice' => '$0.00',
                                        ],
                                    ],
                                    'productRetailPrice' => 0.49,
                                    'productDiscountAmount' => '0.00',
                                    'productLinePrice' => '0.49',
                                    'editable' => '',
                                ],
                            ],
                            'grossAmount' => '$0.49',
                            'discounts' => [],
                            'totalDiscountAmount' => '$0.00',
                            'netAmount' => '$0.49',
                            'taxableAmount' => '$0.49',
                            'taxAmount' => '$0.00',
                            'totalAmount' => '$0.49',
                            'estimatedVsActual' => 'ACTUAL',
                            'discounts' => [],
                        ],
                    ],

                ],
            ],
        ];

        $this->assertNull($this->fXORateHelper->resetCartDiscounts($this->quote, $productRates));
    }

    /**
     * Test GetFXORate Without Commercial Customer.
     *
     * @return void
     */
    public function testGetFXORateWithoutCommericialCustomer()
    {
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->quote->expects($this->any())->method('getData')->willReturn('XYZ');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->quoteItemCollectionFactory->expects($this->any())->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())->method('getAttributeSetName')->willReturn('XYZ');
        $this->item->expects($this->any())->method('getOptionByCode')
            ->willReturn($this->option);
        $externalData = '{"external_prod":[{"userProductName":"Poster
            Prints","id":"1466693799380","version":2,"name":"Posters",
            "qty":1,"priceable":true,"instanceId":1632939962051}]}';

        $this->serializer->expects($this->any())->method('unserialize')->willReturn(json_encode($externalData));
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($externalData));

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate With Attribute set Name.
     *
     * @return void
     */
    public function testGetFXORateWithattributeSetName()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(false);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())
            ->method('getFedexAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->quote->expects($this->any())->method('getData')->willReturn('XYZ');
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->quoteItemCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())
            ->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())
            ->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);

        $optionData = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => '',
                    ],
                ],
            ],
        ];
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($optionData));
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($optionData);

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate With Pickup is true and Pickup Data.
     *
     * @return void
     */
    public function testGetFXORateWithisPickUpAndArrayPickupdata()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(false);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())
            ->method('getFedexAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );

        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->quote->expects($this->any())->method('getData')->willReturn("XYZ");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->quoteItemCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);
        $this->quote->expects($this->any())->method('getCustomerPickupLocationData')->willReturn(
            [
                'locationId'         => 12,
                'fedExAccountNumber' => 12345678,
            ]
        );
        $optionData = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => '',
                    ],
                ],
            ],
        ];
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($optionData));
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($optionData);

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate With Shipment is true and Shipping Data .
     *
     * @return void
     */
    public function testGetFXORateWithGivenArrayShipment()
    {
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())
            ->method('getIsFromShipping')
            ->willReturn(true);
        $this->quote->expects($this->any())
            ->method('getIsFromPickup')
            ->willReturn(false);
        $this->quote->expects($this->any())
            ->method('getIsFromAccountScreen')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(false);
        $this->deliveryHelper->expects($this->any())
            ->method('getCompanySite')
            ->willReturn(true);
        $this->companyHelper->expects($this->any())
            ->method('getCompanyPaymentMethod')->willReturn('fedexaccountnumber');
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(12345678);
        $this->deliveryHelper->expects($this->any())
            ->method('getGateToken')
            ->willReturn(true);
        $this->deliveryHelper->expects($this->any())
            ->method('getApiToken')
            ->willReturn($this->apiToken);
        $this->punchoutHelper->expects($this->any())
            ->method('getGatewayToken')
            ->willReturn(true);
        $this->punchoutHelper->expects($this->any())
            ->method('getTazToken')
            ->willReturn(
                json_encode(
                    [
                        'access_token' => '123',
                        'token_type'   => 'Cookie',
                    ]
                )
            );
        $this->companyHelper->expects($this->any())->method('getFedexAccountNumber')->willReturn(123456789);
        $this->quote->expects($this->any())->method('getData')->willReturn("XYZ");
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->quoteItemCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->quoteItemCollection);
        $this->quoteItemCollection->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->quoteItemCollection->expects($this->any())->method('getSize')->willReturn(1);
        $this->item->expects($this->any())->method('getProduct')->willReturn($this->product);
        $this->product->expects($this->any())->method('getId')->willReturn(2);
        $this->productModel->expects($this->any())->method('load')->with(2)->willReturn($this->product);
        $this->product->expects($this->any())->method('getAttributeSetId')->willReturn(2);
        $this->attributeSetRepositoryInterface->expects($this->any())
            ->method('get')->willReturn($this->attributeSetInterface);
        $this->attributeSetInterface->expects($this->any())
            ->method('getAttributeSetName')->willReturn('FXOPrintProducts');
        $this->item->expects($this->any())->method('getOptionByCode')->willReturn($this->option);

        $this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(0);

        $this->quote->expects($this->any())->method('getCustomerShippingAddress')->willReturn(
            [
                'street' => 'XYZ',
                'city' => 'XYZ',
                'regionData' => 1234,
                'zipcode' => 1771,
                'addrClassification' => 'HOME',
                'shipMethod' => 'test',
                'fedExAccountNumber' => 12345678,
                'fedExShippingAccountNumber' => 12345678,
                'productionLocationId' => null
            ]
        );
        $optionData = [
            'external_prod' => [
                '0' => [
                    'catalogReference' => 'true',
                    'preview_url' => 'false',
                    'fxo_product' => 'name',
                    'instanceId' => [
                        '@xattributes' => '',
                        'xItemID' => '',
                    ],
                ],
            ],
        ];
        $this->option->expects($this->any())->method('getValue')->willReturn(json_encode($optionData));
        $this->serializer->expects($this->any())->method('unserialize')->willReturn($optionData);
        $signatureOptions = [
            'specialServiceType' => 'SIGNATURE_OPTION',
            'specialServiceSubType' => 'DIRECT',
            'displayText' => 'Direct Signature Required',
            'description' => 'Direct Signature Required',
        ];
        $specialServices = [$signatureOptions];
        $this->deliveryHelper->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')
            ->willReturn($specialServices);

        $this->assertNotNull($this->fXORateHelper->getFXORate($this->quote));
    }

    /**
     * Test GetFXORate With remove item from cart
     *
     * @return void
     */
    public function testRemoveReorderQuoteItem()
    {
        $rateApiResonse = [
            0 => [
                'code' => 'INTERNAL.SERVER.FAILURE',
                'message' => 'The items could not be added'
            ]
        ];
        $rateApiResonseElseIf1 = [
            0 => [
                'code' => 'RATEREQUEST.PRODUCTS.INVALID',
                'message' => 'System Error : 123'
            ]
        ];
        $rateApiResonseElseIf2 = [
            0 => [
                'code' => 'PRODUCTS.CATALOGREFERENCE.INVALID',
                'message' => 'System Error : 123'
            ]
        ];
        $rateApiResonseElseIf3 = [
            0 => [
                'code' => 'SYSTEM.ERROR',
                'message' => 'System Error : 123'
            ]
        ];

        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getCustomPrice')->willReturn('');
        $this->quote->expects($this->any())->method('deleteItem')->with($this->item)->willReturnSelf();
        $this->cart->method('save')->willReturnSelf();
        $this->fXORateHelper->removeReorderQuoteItem($this->quote, $rateApiResonse);
        $this->fXORateHelper->removeReorderQuoteItem($this->quote, $rateApiResonseElseIf1);
        $this->fXORateHelper->removeReorderQuoteItem($this->quote, $rateApiResonseElseIf2);

        $this->assertNotNull($this->fXORateHelper->removeReorderQuoteItem($this->quote, $rateApiResonseElseIf3));
    }

    /**
     * Reset Fedex Account Test case
     * @return void
     */
    public function testResetFedexAccount()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'message' => 'FedEx account is invalid'
                ]
            ]
        ];
        $this->quote->expects($this->once())->method('getData')->willReturn('12345678');
        $this->cartDataHelper->expects($this->any())->method('decryptData')->willReturn('12345678');
        $this->customerSession->expects($this->any())->method('setFedexAccountWarning')->willReturn('123454678');

        $this->fXORateHelper->resetFedexAccount($rateResponse, $this->quote);
    }

    /**
     * Test remove quote item
     * @return void
     */
    public function testremoveQuoteItem()
    {
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->item]);
        $this->item->expects($this->any())->method('getCustomPrice')->willReturn(false);
        $this->quote->expects($this->any())->method('deleteItem')->willReturn(true);
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->quoteRepositoryMock->expects($this->any())->method('save')->with($this->quote)->willReturnSelf();

        $this->assertNull($this->fXORateHelper->removeQuoteItem($this->quote));
    }

    /**
     * Test case for removePromoCode
     */
    public function testRemovePromoCodeWithInvalid()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.INVALID'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');
        $this->customerSession->expects($this->any())->method('setPromoErrorMessage')->willReturn('aaa');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for pruchase required
     * @return void
     */
    public function testRemovePromoCodeWithMinimumPurchaseRequired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'MINIMUM.PURCHASE.REQUIRED'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code invalid
     * @return void
     */
    public function testRemovePromoCodeWithInvalidProductCode()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'INVALID.PRODUCT.CODE',
                    'message' => 'promo code is invalid'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code for coupon expire
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponExpired()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.EXPIRED'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemed()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.REDEEMED'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedWithFalse()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => 'COUPONS.CODE.REDEEMED'
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn('MGT001');

        $this->assertNotNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote, false));
    }

    /**
     * Test rest promo code  for coupon reedemed
     * @return void
     */
    public function testRemovePromoCodeWithCodeCouponReedemedWithNull()
    {
        $rateResponse = [
            'alerts' => [
                0 => [
                    'code' => null
                ]
            ]
        ];
        $this->cartFactory->expects($this->any())->method('create')->willReturn($this->cart);
        $this->cart->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getCouponCode')->willReturn("UAT001");

        $this->assertNull($this->fXORateHelper->removePromoCode($rateResponse, $this->quote, false));
    }

    public function testIsEproCustomer()
    {
        $this->deliveryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->assertTrue($this->fXORateHelper->isEproCustomer());
    }

    /**
     * Test method for manageCartWarnings
     * @return void
     */
    public function testManageCartWarnings()
    {
        $rateResponse = [
            'transactionId' => '6d1dd203-a1db-494b-a59b-e63c8e9a8042',
            'output' => [
                'alerts' => [
                    0 => [
                        'code' => 'ADDRESS_SERVICE_FAILURE'
                    ],
                    1 => [
                        'code' => 'MAX.PRODUCT.COUNT'
                    ]
                ]
            ],
        ];

        $this->assertNotNull($this->fXORateHelper->manageCartWarnings($rateResponse));
    }

    private function getInfoBuyRequestMock()
    {
        return '{
        "external_prod":[
            {
                "productionContentAssociations":[

                ],
                "userProductName":"New Site Setup Doc v1.0",
                "id":"1456773326927",
                "version":2,
                "name":"Multi Sheet",
                "qty":1,
                "priceable":true,
                "instanceId":"0",
                "proofRequired":false,
                "isOutSourced":false,
                "minDPI":"150.0",
                "features":[
                    {
                    "id":"1448981549109",
                    "name":"Paper Size",
                    "choice":{
                        "id":"1448986650332",
                        "name":"8.5x11",
                        "properties":[
                            {
                                "id":"1449069906033",
                                "name":"MEDIA_HEIGHT",
                                "value":"11"
                            },
                            {
                                "id":"1449069908929",
                                "name":"MEDIA_WIDTH",
                                "value":"8.5"
                            },
                            {
                                "id":"1571841122054",
                                "name":"DISPLAY_HEIGHT",
                                "value":"11"
                            },
                            {
                                "id":"1571841164815",
                                "name":"DISPLAY_WIDTH",
                                "value":"8.5"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448981549741",
                    "name":"Paper Type",
                    "choice":{
                        "id":"1448988664295",
                        "name":"Laser (32 lb.)",
                        "properties":[
                            {
                                "id":"1450324098012",
                                "name":"MEDIA_TYPE",
                                "value":"E32"
                            },
                            {
                                "id":"1453234015081",
                                "name":"PAPER_COLOR",
                                "value":"#FFFFFF"
                            },
                            {
                                "id":"1471275182312",
                                "name":"MEDIA_CATEGORY",
                                "value":"RESUME"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448981549581",
                    "name":"Print Color",
                    "choice":{
                        "id":"1448988600611",
                        "name":"Full Color",
                        "properties":[
                            {
                                "id":"1453242778807",
                                "name":"PRINT_COLOR",
                                "value":"COLOR"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448981549269",
                    "name":"Sides",
                    "choice":{
                        "id":"1448988124560",
                        "name":"Single-Sided",
                        "properties":[
                            {
                                "id":"1461774376168",
                                "name":"SIDE",
                                "value":"SINGLE"
                            },
                            {
                                "id":"1471294217799",
                                "name":"SIDE_VALUE",
                                "value":"1"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448984679218",
                    "name":"Orientation",
                    "choice":{
                        "id":"1449000016192",
                        "name":"Vertical",
                        "properties":[
                            {
                                "id":"1453260266287",
                                "name":"PAGE_ORIENTATION",
                                "value":"PORTRAIT"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448981554101",
                    "name":"Prints Per Page",
                    "choice":{
                        "id":"1448990257151",
                        "name":"One",
                        "properties":[
                            {
                                "id":"1455387404922",
                                "name":"PRINTS_PER_PAGE",
                                "value":"1"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448984877869",
                    "name":"Cutting",
                    "choice":{
                        "id":"1448999392195",
                        "name":"None",
                        "properties":[

                        ]
                    }
                    },
                    {
                    "id":"1448981555573",
                    "name":"Hole Punching",
                    "choice":{
                        "id":"1448999902070",
                        "name":"None",
                        "properties":[

                        ]
                    }
                    },
                    {
                    "id":"1448984679442",
                    "name":"Lamination",
                    "choice":{
                        "id":"1448999458409",
                        "name":"None",
                        "properties":[

                        ]
                    }
                    },
                    {
                    "id":"1448981532145",
                    "name":"Collation",
                    "choice":{
                        "id":"1448986654687",
                        "name":"Collated",
                        "properties":[
                            {
                                "id":"1449069945785",
                                "name":"COLLATION_TYPE",
                                "value":"MACHINE"
                            }
                        ]
                    }
                    },
                    {
                    "id":"1448981554597",
                    "name":"Binding",
                    "choice":{
                        "id":"1448997199553",
                        "name":"None",
                        "properties":[

                        ]
                    }
                    }
                ],
                "pageExceptions":[

                ],
                "contentAssociations":[
                    {
                    "parentContentReference":"13289803111109458622407630941251144657984",
                    "contentReference":"13289803113162084968918107930851905699681",
                    "contentType":"WORD",
                    "fileName":"New Site Setup Doc v1.0.docx",
                    "contentReqId":"1483999952979",
                    "name":"Multi Sheet",
                    "desc":null,
                    "purpose":"MAIN_CONTENT",
                    "specialInstructions":"",
                    "printReady":true,
                    "pageGroups":[
                        {
                            "start":1,
                            "end":10,
                            "width":8.5,
                            "height":11,
                            "orientation":"PORTRAIT"
                        }
                    ]
                    }
                ],
                "properties":[
                    {
                    "id":"1453242488328",
                    "name":"ZOOM_PERCENTAGE",
                    "value":"60"
                    },
                    {
                    "id":"1453243262198",
                    "name":"ENCODE_QUALITY",
                    "value":"100"
                    },
                    {
                    "id":"1453894861756",
                    "name":"LOCK_CONTENT_ORIENTATION",
                    "value":false
                    },
                    {
                    "id":"1453895478444",
                    "name":"MIN_DPI",
                    "value":"150.0"
                    },
                    {
                    "id":"1454950109636",
                    "name":"USER_SPECIAL_INSTRUCTIONS",
                    "value":null
                    },
                    {
                    "id":"1455050109636",
                    "name":"DEFAULT_IMAGE_WIDTH",
                    "value":"8.5"
                    },
                    {
                    "id":"1455050109631",
                    "name":"DEFAULT_IMAGE_HEIGHT",
                    "value":"11"
                    },
                    {
                    "id":"1494365340946",
                    "name":"PREVIEW_TYPE",
                    "value":"DYNAMIC"
                    },
                    {
                    "id":"1470151626854",
                    "name":"SYSTEM_SI",
                    "value":null
                    },
                    {
                    "id":"1470151737965",
                    "name":"TEMPLATE_AVAILABLE",
                    "value":"NO"
                    },
                    {
                    "id":"1490292304798",
                    "name":"MIGRATED_PRODUCT",
                    "value":"true"
                    }
                ]
            }
        ],
        "productConfig":{
            "productPresetId":"1602518818916",
            "fileCreated":"{{datetime}}",
            "currentProjectContainLowResFile":true
        },
        "productRateTotal":{
            "unitPrice":null,
            "currency":"USD",
            "quantity":50,
            "price":"$76.00",
            "priceAfterDiscount":"$76.00",
            "unitOfMeasure":"EACH",
            "totalDiscount":"$0.00",
            "productLineDetails":[
                {
                    "detailCode":"40005",
                    "priceRequired":false,
                    "priceOverridable":false,
                    "description":"FullPgClrFlyr50",
                    "unitQuantity":1,
                    "quantity":1,
                    "detailPrice":"$76.00",
                    "detailDiscountPrice":"$0.00",
                    "detailUnitPrice":"$76.0000",
                    "detailDiscountedUnitPrice":"$0.0000",
                    "detailCategory":"PRINTING"
                }
            ]
        },
        "quantityChoices":[
            "50",
            "100",
            "250",
            "500",
            "1000"
        ],
        "fileManagementState":{
            "availableFileItems":[
                {
                    "file":[

                    ],
                    "fileItem":{
                    "fileId":"13270448494194258340615212132711635485970",
                    "fileName":"flyer.png",
                    "fileExtension":"png",
                    "fileSize":568785,
                    "createdTimestamp":"{{datetime}}"
                    },
                    "uploadStatus":"Success",
                    "errorMsg":"",
                    "uploadProgressPercentage":100,
                    "uploadProgressBytesLoaded":568972,
                    "selected":false,
                    "httpRsp":{
                    "successful":true,
                    "output":{
                        "document":{
                            "documentId":"13270448494194258340615212132711635485970",
                            "documentName":"flyer.png",
                            "documentSize":568783,
                            "printReady":false
                        }
                    }
                    }
                }
            ],
            "projects":[
                {
                    "fileItems":[
                    {
                        "uploadStatus":"Success",
                        "errorMsg":"",
                        "selected":false,
                        "originalFileItem":{
                            "fileId":"13270448494194258340615212132711635485970",
                            "fileName":"flyer.png",
                            "fileExtension":"png",
                            "fileSize":568785,
                            "createdTimestamp":"{{datetime}}"
                        },
                        "convertStatus":"Success",
                        "convertedFileItem":{
                            "fileId":"13270448495033844498615764234401464873348",
                            "fileName":"flyer.png",
                            "fileExtension":"pdf",
                            "fileSize":496362,
                            "createdTimestamp":"{{datetime}}",
                            "numPages":1
                        },
                        "orientation":"PORTRAIT",
                        "conversionResult":{
                            "parentDocumentId":"13270448494194258340615212132711635485970",
                            "originalDocumentName":"flyer.png",
                            "printReadyFlag":true,
                            "previewURI":
                            "https:\/\/dunc6.dmz.fedex.com\/document\/fedexoffice\/v1\/documents\/
                            13270448495033844498615764234401464873348\/preview",
                            "documentSize":496362,
                            "documentType":"IMAGE",
                            "lowResImage":true,
                            "documentId":"13270448495033844498615764234401464873348",
                            "metrics":{
                                "pageCount":1,
                                "pageGroups":[
                                {
                                    "startPageNum":1,
                                    "endPageNum":1,
                                    "pageWidthInches":8.5,
                                    "pageHeightInches":11
                                }
                                ]
                            }
                        },
                        "contentAssociation":{
                            "parentContentReference":"13270448494194258340615212132711635485970",
                            "contentReference":"13270448495033844498615764234401464873348",
                            "contentType":"IMAGE",
                            "fileSizeBytes":"496362",
                            "fileName":"flyer.png",
                            "printReady":true,
                            "pageGroups":[
                                {
                                "start":1,
                                "end":1,
                                "width":8.5,
                                "height":11,
                                "orientation":"PORTRAIT"
                                }
                            ],
                            "contentReqId":"1455709847200",
                            "name":"Front_Side",
                            "desc":null,
                            "purpose":"SINGLE_SHEET_FRONT",
                            "specialInstructions":""
                        },
                        "lowResImage":true
                    }
                    ],
                    "projectName":"flyer",
                    "productId":"1463680545590",
                    "productPresetId":"1602518818916",
                    "productVersion":null,
                    "controlId":"4",
                    "maxFiles":2,
                    "productType":"Flyers",
                    "availableSizes":"8.5\"x11\"",
                    "convertStatus":"Success",
                    "showInList":true,
                    "firstInList":false,
                    "accordionOpen":true,
                    "needsToBeConverted":false,
                    "selected":false,
                    "mayContainUserSelections":false,
                    "hasUserChangedProjectNameManually":false,
                    "projectId":6089506769,
                    "supportedProductSizes":{
                    "featureId":"1448981549109",
                    "featureName":"Size",
                    "choices":[
                        {
                            "choiceId":"1448986650332",
                            "choiceName":"8.5\"x11\"",
                            "properties":[
                                {
                                "name":"MEDIA_HEIGHT",
                                "value":"11"
                                },
                                {
                                "name":"MEDIA_WIDTH",
                                "value":"8.5"
                                },
                                {
                                "name":"DISPLAY_HEIGHT",
                                "value":"11"
                                },
                                {
                                "name":"DISPLAY_WIDTH",
                                "value":"8.5"
                                }
                            ]
                        }
                    ]
                    },
                    "productConfig":{
                    "product":{
                        "productionContentAssociations":[

                        ],
                        "userProductName":"flyer",
                        "id":"1463680545590",
                        "version":1,
                        "name":"Flyer",
                        "qty":50,
                        "priceable":true,
                        "instanceId":1682539128505,
                        "proofRequired":false,
                        "isOutSourced":false,
                        "features":[
                            {
                                "id":"1448981549109",
                                "name":"PaperSize",
                                "choice":{
                                "id":"1448986650332",
                                "name":"8.5x11",
                                "properties":[
                                    {
                                        "id":"1449069906033",
                                        "name":"MEDIA_HEIGHT",
                                        "value":"11"
                                    },
                                    {
                                        "id":"1449069908929",
                                        "name":"MEDIA_WIDTH",
                                        "value":"8.5"
                                    },
                                    {
                                        "id":"1571841122054",
                                        "name":"DISPLAY_HEIGHT",
                                        "value":"11"
                                    },
                                    {
                                        "id":"1571841164815",
                                        "name":"DISPLAY_WIDTH",
                                        "value":"8.5"
                                    }
                                ]
                                }
                            },
                            {
                                "id":"1448981549581",
                                "name":"PrintColor",
                                "choice":{
                                "id":"1448988600611",
                                "name":"FullColor",
                                "properties":[
                                    {
                                        "id":"1453242778807",
                                        "name":"PRINT_COLOR",
                                        "value":"COLOR"
                                    }
                                ]
                                }
                            },
                            {
                                "id":"1448981549269",
                                "name":"Sides",
                                "choice":{
                                "id":"1448988124560",
                                "name":"Single-Sided",
                                "properties":[
                                    {
                                        "id":"1470166759236",
                                        "name":"SIDE_NAME",
                                        "value":"SingleSided"
                                    },
                                    {
                                        "id":"1461774376168",
                                        "name":"SIDE",
                                        "value":"SINGLE"
                                    }
                                ]
                                }
                            },
                            {
                                "id":"1448984679218",
                                "name":"Orientation",
                                "choice":{
                                "id":"1449000016192",
                                "name":"Vertical",
                                "properties":[
                                    {
                                        "id":"1453260266287",
                                        "name":"PAGE_ORIENTATION",
                                        "value":"PORTRAIT"
                                    }
                                ]
                                }
                            },
                            {
                                "id":"1534920174638",
                                "name":"Envelope",
                                "choice":{
                                "id":"1634129308274",
                                "name":"None",
                                "properties":[

                                ]
                                }
                            },
                            {
                                "id":"1448981549741",
                                "name":"PaperType",
                                "choice":{
                                "id":"1448988666879",
                                "name":"GlossText",
                                "properties":[
                                    {
                                        "id":"1450324098012",
                                        "name":"MEDIA_TYPE",
                                        "value":"CT"
                                    },
                                    {
                                        "id":"1453234015081",
                                        "name":"PAPER_COLOR",
                                        "value":"#FFFFFF"
                                    },
                                    {
                                        "id":"1470166630346",
                                        "name":"MEDIA_NAME",
                                        "value":"GlossText"
                                    },
                                    {
                                        "id":"1471275182312",
                                        "name":"MEDIA_CATEGORY",
                                        "value":"TEXT_GLOSS"
                                    }
                                ]
                                }
                            }
                        ],
                        "pageExceptions":[

                        ],
                        "contentAssociations":[
                            {
                                "parentContentReference":"13270448494194258340615212132711635485970",
                                "contentReference":"13270448495033844498615764234401464873348",
                                "contentType":"IMAGE",
                                "fileName":"flyer.png",
                                "contentReqId":"1455709847200",
                                "name":"Front_Side",
                                "desc":null,
                                "purpose":"SINGLE_SHEET_FRONT",
                                "specialInstructions":"",
                                "printReady":true,
                                "pageGroups":[
                                {
                                    "start":1,
                                    "end":1,
                                    "width":8.5,
                                    "height":11,
                                    "orientation":"PORTRAIT"
                                }
                                ]
                            }
                        ],
                        "properties":[
                            {
                                "id":"1453242488328",
                                "name":"ZOOM_PERCENTAGE",
                                "value":"60"
                            },
                            {
                                "id":"1453243262198",
                                "name":"ENCODE_QUALITY",
                                "value":"100"
                            },
                            {
                                "id":"1453894861756",
                                "name":"LOCK_CONTENT_ORIENTATION",
                                "value":false
                            },
                            {
                                "id":"1453895478444",
                                "name":"MIN_DPI",
                                "value":"150.0"
                            },
                            {
                                "id":"1454950109636",
                                "name":"USER_SPECIAL_INSTRUCTIONS",
                                "value":null
                            },
                            {
                                "id":"1455050109636",
                                "name":"DEFAULT_IMAGE_WIDTH",
                                "value":"8.5"
                            },
                            {
                                "id":"1455050109631",
                                "name":"DEFAULT_IMAGE_HEIGHT",
                                "value":"11"
                            },
                            {
                                "id":"1464709502522",
                                "name":"PRODUCT_QTY_SET",
                                "value":"50"
                            },
                            {
                                "id":"1459784717507",
                                "name":"SKU",
                                "value":"2821"
                            },
                            {
                                "id":"1470151626854",
                                "name":"SYSTEM_SI",
                                "value":
                                "ATTENTIONTEAMMEMBER:
                                Usethefollowinginstructionstoproducethisorder.
                                DONOTusetheProductionInstructionslistedabove.
                                FlyerPackagespecifications:Yield50,SingleSidedColor8.5x11GlossText(CT),FullPage."
                            },
                            {
                                "id":"1494365340946",
                                "name":"PREVIEW_TYPE",
                                "value":"DYNAMIC"
                            },
                            {
                                "id":"1470151737965",
                                "name":"TEMPLATE_AVAILABLE",
                                "value":"YES"
                            },
                            {
                                "id":"1459784776049",
                                "name":"PRICE",
                                "value":null
                            },
                            {
                                "id":"1490292304798",
                                "name":"MIGRATED_PRODUCT",
                                "value":"true"
                            },
                            {
                                "id":"1558382273340",
                                "name":"PNI_TEMPLATE",
                                "value":"NO"
                            },
                            {
                                "id":"1602530744589",
                                "name":"CONTROL_ID",
                                "value":"4"
                            },
                            {
                                "id":"1614715469176",
                                "name":"IMPOSE_TEMPLATE_ID",
                                "value":"0"
                            }
                        ],
                        "minDPI":"150.0"
                    },
                    "productPresetId":"1602518818916",
                    "fileCreated":"{{{{datetime}}}}",
                    "currentProjectContainLowResFile":true
                    }
                }
            ],
            "catalogManageFilesToggle":true,
            "displayErrorIds":false
        },
        "fxoMenuId":"1614105200640-4"
            }';
    }

    public function testSaveDiscountBreakdownWithDiscountsAndToggleOn()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => [
                        [
                            'productLines' => [
                                [
                                    'productLineDiscounts' => [
                                        ['type' => 'AR_CUSTOMERS', 'amount' => '(10.00)'],
                                        ['type' => 'QUANTITY', 'amount' => '(5.00)'],
                                    ]
                                ]
                            ],
                            'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => '(1.00)']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(true);

        $this->quote->expects($this->any())->method('setPromoDiscount')->willReturnSelf();
        $this->quote->expects($this->once())->method('setAccountDiscount')->with(10.00);
        $this->quote->expects($this->once())->method('setVolumeDiscount')->with(5.00);
        $this->quote->expects($this->once())->method('setShippingDiscount')->with(1.00);
        $this->quote->expects($this->once())->method('save');

        $this->assertEquals(true, $this->fXORateHelper->saveDiscountBreakdown($this->quote, $rateApiOutputdata));
    }

    public function testSaveDiscountBreakdownWithDiscountsAndToggleOff()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => [
                        [
                            'discounts' => [
                                ['type' => 'AR_CUSTOMERS', 'amount' => '(15.00)'],
                                ['type' => 'QUANTITY', 'amount' => '(7.50)'],
                                // ['type' => 'COUPON', 'amount' => '(3.00)']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(false);

        $this->quote->expects($this->any())->method('setPromoDiscount')->willReturnSelf();
        $this->quote->expects($this->any())->method('setAccountDiscount')->willReturnSelf();
        $this->quote->expects($this->any())->method('setVolumeDiscount')->willReturnSelf();
        $this->quote->expects($this->any())->method('setShippingDiscount')->willReturnSelf();
        $this->quote->expects($this->any())->method('save');

        $this->assertEquals(true, $this->fXORateHelper->saveDiscountBreakdown($this->quote, $rateApiOutputdata));
    }

    public function testSaveDiscountBreakdownWithoutDiscounts()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => []
                ]
            ]
        ];

        $this->quote->expects($this->once())->method('setVolumeDiscount')->with(0);
        $this->quote->expects($this->once())->method('setAccountDiscount')->with(0);
        $this->quote->expects($this->once())->method('setPromoDiscount')->with(0);
        $this->quote->expects($this->once())->method('save');

        $this->assertEquals(true, $this->fXORateHelper->saveDiscountBreakdown($this->quote, $rateApiOutputdata));
    }

    public function testGetDiscountsWithToggleOn()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => [
                        [
                            'productLines' => [
                                [
                                    'productLineDiscounts' => [
                                        ['type' => 'AR_CUSTOMERS', 'amount' => '(10.00)']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(true);

        $result = $this->fXORateHelper->getDiscounts($rateApiOutputdata);

        $this->assertNotEmpty($result);
        $this->assertEquals('AR_CUSTOMERS', $result[0][0]['type']);
        $this->assertEquals('(10.00)', $result[0][0]['amount']);
    }

    public function testGetDiscountsWithToggleOff()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => [
                        [
                            'discounts' => [
                                ['type' => 'QUANTITY', 'amount' => '(5.00)']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(false);

        $result = $this->fXORateHelper->getDiscounts($rateApiOutputdata);

        $this->assertNotEmpty($result);
        $this->assertEquals('QUANTITY', $result[0]['type']);
        $this->assertEquals('(5.00)', $result[0]['amount']);
    }

    public function testGetShippingDiscount()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => [
                        [
                            'deliveryLines' => [
                                [
                                    'deliveryLineDiscounts' => [
                                        ['type' => 'COUPON', 'amount' => 2.00]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->fXORateHelper->getShippingDiscount($rateApiOutputdata);

        $this->assertEquals(2.00, $result);
    }

    public function testGetShippingDiscountWithoutDiscounts()
    {
        $rateApiOutputdata = [
            'output' => [
                'rate' => [
                    'rateDetails' => []
                ]
            ]
        ];

        $result = $this->fXORateHelper->getShippingDiscount($rateApiOutputdata);

        $this->assertEquals(0, $result);
    }

    public function testResetDiscountWithToggleOnAndAllDiscountsPresent()
    {
        $discounts = [
            [
                ['type' => 'QUANTITY'],
                ['type' => 'AR_CUSTOMERS'],
                ['type' => 'COUPON']
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(true);

        $this->quote->expects($this->never())->method('setVolumeDiscount');
        $this->quote->expects($this->never())->method('setAccountDiscount');
        $this->quote->expects($this->never())->method('setPromoDiscount');

        $result = $this->fXORateHelper->resetDiscount($this->quote, $discounts);

        $this->assertTrue($result);
    }

    public function testResetDiscountWithToggleOnAndMissingDiscounts()
    {
        $discounts = [
            [
                ['type' => 'AR_CUSTOMERS']
            ]
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(true);

        $this->quote->expects($this->once())->method('setVolumeDiscount')->with(0);
        $this->quote->expects($this->once())->method('setPromoDiscount')->with(0);
        $this->quote->expects($this->never())->method('setAccountDiscount');

        $result = $this->fXORateHelper->resetDiscount($this->quote, $discounts);

        $this->assertTrue($result);
    }

    public function testResetDiscountWithToggleOffAndAllDiscountsPresent()
    {
        $discounts = [
            ['type' => 'QUANTITY'],
            ['type' => 'AR_CUSTOMERS'],
            ['type' => 'COUPON']
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(false);

        $this->quote->expects($this->never())->method('setVolumeDiscount');
        $this->quote->expects($this->never())->method('setAccountDiscount');
        $this->quote->expects($this->never())->method('setPromoDiscount');

        $result = $this->fXORateHelper->resetDiscount($this->quote, $discounts);

        $this->assertTrue($result);
    }

    public function testResetDiscountWithToggleOffAndMissingDiscounts()
    {
        $discounts = [
            ['type' => 'CORPORATE']
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(false);

        $this->quote->expects($this->once())->method('setVolumeDiscount')->with(0);
        $this->quote->expects($this->once())->method('setPromoDiscount')->with(0);
        $this->quote->expects($this->never())->method('setAccountDiscount');

        $result = $this->fXORateHelper->resetDiscount($this->quote, $discounts);

        $this->assertTrue($result);
    }

    public function testResetDiscountWithToggleOffAndMissingCOUPON()
    {
        $discounts = [
            ['type' => 'COUPON']
        ];

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->with('mazegeek_B2352379_discount_breakdown')
            ->willReturn(false);

        $this->quote->expects($this->any())->method('setVolumeDiscount')->with(0);
        $this->quote->expects($this->any())->method('setPromoDiscount')->with(0);
        $this->quote->expects($this->any())->method('setAccountDiscount');

        $result = $this->fXORateHelper->resetDiscount($this->quote, $discounts);

        $this->assertTrue($result);
    }
}
