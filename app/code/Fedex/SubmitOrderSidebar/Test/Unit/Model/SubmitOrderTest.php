<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Model;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Fedex\SubmitOrderSidebar\Helper\Data as SubmitOrderHelper;
use Magento\Catalog\Model\Product\Configuration\Item\ItemInterface;
use Magento\Checkout\Model\CartFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\SubmitOrderSidebar\Model\SubmitOrder as SubmitOrderModel;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;

class SubmitOrderTest extends TestCase
{
    protected $cartFactoryMock;
    /**
     * @var (\Magento\Checkout\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $checkoutSessionMock;
    protected $helperMock;
    protected $punchoutHelperMock;
    protected $submitOrderHelperMock;
    protected $addressInterfaceMock;
    protected $billingAddressBuilderMock;
    /**
     * @var (\Magento\Framework\DataObjectFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $dataObjectFactory;
    protected $cartMock;
    protected $storeManagerMock;
    protected $customerSessionMock;
    protected $regionFactoryMock;
    protected $regionMock;
    protected $quoteMock;
    protected $customerRepositoryMock;
    protected $itemMock;
    protected $itemOptionMock;
    /**
     * @var (\Fedex\MarketplaceProduct\Helper\Quote & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteHelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $submitOrderMock;
    /**
     * @return void
     */
    public function setUp() : void
    {
        $this->cartFactoryMock = $this->createMock(CartFactory::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->helperMock = $this->createMock(DeliveryHelper::class);
        $this->punchoutHelperMock = $this->createMock(PunchoutHelper::class);
        $this->submitOrderHelperMock = $this->createMock(SubmitOrderHelper::class);
        $this->addressInterfaceMock = $this->createMock(AddressInterface::class);
        $this->billingAddressBuilderMock = $this->createMock(BillingAddressBuilder::class);
        $this->dataObjectFactory = $this->createMock(DataObjectFactory::class);
        $this->cartMock = $this->getMockBuilder(Cart::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(["getStore", "getBaseUrl"])
            ->getMockForAbstractClass();
        $this->customerSessionMock = $this->getMockBuilder(CustomerSession::class)
            ->setMethods(['getCustomerId', 'getCustomerCompany'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->regionFactoryMock = $this->createMock(RegionFactory::class);
        $this->regionMock = $this->getMockBuilder(Region::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['getBillingAddress','getAllItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->setMethods(['getOptionByCode', 'getQty', 'getName', 'getProductId', 'getId', 'getPrice', 'getDiscount'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemOptionMock = $this->getMockBuilder(Option::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quoteHelper = $this->createMock(QuoteHelper::class);

        $this->objectManager = new ObjectManager($this);
        $this->submitOrderMock = $this->objectManager->getObject(
            SubmitOrder::class,
            [
                'cartFactory'           => $this->cartFactoryMock,
                'checkoutSession'       => $this->checkoutSessionMock,
                'helper'                => $this->helperMock,
                'punchoutHelper'        => $this->punchoutHelperMock,
                'regionFactory'         => $this->regionFactoryMock,
                'submitOrderHelper'     => $this->submitOrderHelperMock,
                'storeManager'          => $this->storeManagerMock,
                'billingAddressBuilder' => $this->billingAddressBuilderMock,
                'customerSession'       => $this->customerSessionMock,
                'dataObjectFactory'     => $this->dataObjectFactory,
                'quoteHelper'           => $this->quoteHelper
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetQuote():void
    {
        $this->cartFactoryMock->expects($this->any())
            ->method('create')->willReturn($this->cartMock);
        $this->cartMock->expects($this->any())
            ->method('getQuote')->willReturnSelf();
        $this->assertEquals($this->cartMock, $this->submitOrderMock->getQuote());
    }

    /**
     * @return void
     */
    public function testGetGTNNumber():void
    {
        $this->punchoutHelperMock->expects($this->any())
            ->method('getGTNNumber')->willReturn('test');
        $this->assertEquals('test', $this->submitOrderMock->getGTNNumber());
    }

    /**
     * @return void
     */
    public function testGetRateRequestShipmentSpecialServices():void
    {
        $this->helperMock->expects($this->any())
            ->method('getRateRequestShipmentSpecialServices')->willReturn([]);
        $this->assertEquals([], $this->submitOrderMock->getRateRequestShipmentSpecialServices());
    }

    /**
     * @return void
     */
    public function testGetRegionByRegionCode(): void
    {
        $this->regionFactoryMock->expects($this->any())->method('create')->willReturn($this->regionMock);
        $this->regionMock->expects($this->any())->method('load')->willReturnSelf();
        $this->assertEquals($this->regionMock, $this->submitOrderMock->getRegionByRegionCode('TX'));
    }

    /**
     * @return string
     */
    public function testGetUuid(): void
    {
        $this->submitOrderHelperMock->expects($this->any())
            ->method('getUuid')->willReturn('test');
        $this->assertEquals('test', $this->submitOrderMock->getUuid());
    }

    /**
     * @return void
     */
    public function testGetBillingAddress(): void
    {
        $paymentData = (object) [
            "paymentMethod" => "cc",
            "nameOnCard" => "Ayush",
            "year" => "2022",
            "expire" => "2028",
            "fedexAccountNumber" => "12345678",
            "encCCData" => "eyJxdW90ZUlkIjoiMDVkMTkwOWMtYzBmZC00OTI5LTgwZm",
            "billingAddress" => (Object) [
                "address" => "Home",
                "addressTwo" => "Home",
                "city" => "Plano",
                "zip" => "75024",
                "state" => "TX",
            ],
        ];
        $this->billingAddressBuilderMock->expects($this->any())
            ->method('build')->willReturn($this->addressInterfaceMock);
        $this->assertEquals(
            $this->addressInterfaceMock,
            $this->submitOrderMock->getBillingAddress($paymentData, $this->quoteMock)
        );
    }

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetWebHookUrl(): void
    {
        $baseUrl = "www.test.com/";
        $url = $baseUrl."rest/V1/fedexoffice/orders/123/status";
        $this->storeManagerMock->expects($this->any())
            ->method('getStore')->willReturnSelf();
        $this->storeManagerMock->expects($this->any())
            ->method('getBaseUrl')->with(UrlInterface::URL_TYPE_WEB)->willReturn($baseUrl);
        $this->assertEquals($url, $this->submitOrderMock->getWebHookUrl('123'));
    }

    /**
     * @return bool
     */
    public function testIsFclCustomer(): void
    {
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($this->customerRepositoryMock);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturnSelf();
        $this->assertFalse($this->submitOrderMock->isFclCustomer());
    }
    /**
     * @return bool
     */
    public function testIsFclCustomerTrue(): void
    {
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomerId')
            ->willReturn($this->customerRepositoryMock);
        $this->customerSessionMock
            ->expects($this->any())
            ->method('getCustomerCompany')
            ->willReturn(false);
        $this->assertTrue($this->submitOrderMock->isFclCustomer());
    }

    /**
     * @return void
     */
    public function testGetProductAndProductAssociations()
    {
        $getValue = json_encode(
            [
                'external_prod' => [
                    '0' => [
                        'catalogReference' => 'value',
                        'preview_url' => 'value2',
                        'fxo_product' => 'value3'
                    ]
                ]
            ]
        );

        $this->itemMock->expects($this->any())
            ->method('getOptionByCode')
            ->willReturn($this->itemOptionMock);

        $this->itemOptionMock->expects($this->any())
            ->method('getValue')
            ->willReturn($getValue);

        $result['product'] = [
            [
                'catalogReference' => [
                    'value'
                ],
                'instanceId' => 0,
                'qty' => null
            ]
        ];

        $result['productAssociations'] = [['id'=>0,'quantity'=>'']];

        $this->assertNotNull($this->submitOrderMock->getProductAndProductAssociations([$this->itemMock], false));
    }

    /**
     * @return void
     */
    public function testUnsetOrderInProgress():void
    {
        $this->assertNull($this->submitOrderMock->unsetOrderInProgress());
    }

    /**
     * @return void
     */
    public function testValidateRateQuoteAPIErrors()
    {
        $result = [0 => ['code' => 'RAQ.SERVICE.119']];
        $this->assertTrue($this->submitOrderMock->validateRateQuoteAPIErrors($result));
    }

    /**
     * @return void
     */
    public function testValidateRateQuoteAPIWarnings()
    {
        $result = [
            0 => [
                'code' => 'QCXS.SERVICE.ORDERNUMBER',
                'message' => 'Order is already exists with orderNumber',
                'alertType' => 'WARNING'
            ]
        ];
        $this->assertTrue($this->submitOrderMock->validateRateQuoteAPIWarnings($result));
    }
}