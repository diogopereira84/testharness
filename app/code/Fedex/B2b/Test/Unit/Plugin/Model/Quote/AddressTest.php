<?php

namespace Fedex\B2b\Test\Unit\Plugin\Model\Quote;

use Fedex\B2b\Plugin\Model\Quote\Address;
use Fedex\GraphQl\Model\RequestQueryValidator;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\Quote\Address\RateRequestFactory;
use Magento\Quote\Model\Quote\Address\RateCollectorInterfaceFactory;
use Fedex\Punchout\Api\Data\ConfigInterface as PunchoutConfigInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Magento\Quote\Model\Quote\TotalsReader;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class AddressTest extends TestCase
{
    /**
     * @var (\Magento\Quote\Model\Quote\Address\RateFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $addressRateFactoryMock;
    protected $quoteModel;
    /**
     * @var (\Fedex\GraphQl\Model\RequestQueryValidator & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestQueryValidatorMock;
    /**
     * @var (\Fedex\InStoreConfigurations\Api\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $instoreConfigMock;
    private $addressPlugin;

    private $toggleConfigMock;
    private $scopeConfigMock;
    private $rateRequestFactoryMock;
    private $punchoutConfigInterfaceMock;
    private $rateCollectorMock;
    private $sessionManagerInterfaceMock;
    private $totalsReaderMock;
    private $quoteHelperMock;
    private $storeManagerMock;
    private $storeMock;

    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->addressRateFactoryMock = $this->createMock(RateFactory::class);
        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
        ->setMethods(['getValue','isSetFlag'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->rateRequestFactoryMock = $this->createMock(RateRequestFactory::class);
        $this->punchoutConfigInterfaceMock = $this->createMock(PunchoutConfigInterface::class);
        $this->rateCollectorMock = $this->getMockBuilder(RateCollectorInterfaceFactory::class)
        ->setMethods(['collectRates','create'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->sessionManagerInterfaceMock = $this->getMockBuilder(SessionManagerInterface::class)
        ->setMethods(['start','getData'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->totalsReaderMock = $this->createMock(TotalsReader::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->quoteHelperMock = $this->createMock(QuoteHelper::class);
        $this->quoteModel = $this->getMockBuilder(QuoteModel::class)
        ->setMethods(['getCustomerEmail','getCustomer','getItemsCollection','getStoreId'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->requestQueryValidatorMock = $this->createMock(RequestQueryValidator::class);
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);

        $this->addressPlugin = new Address(
            $this->scopeConfigMock,
            $this->rateRequestFactoryMock,
            $this->punchoutConfigInterfaceMock,
            $this->rateCollectorMock,
            $this->sessionManagerInterfaceMock,
            $this->quoteHelperMock,
            $this->totalsReaderMock,
            $this->toggleConfigMock,
            $this->addressRateFactoryMock,
            $this->requestQueryValidatorMock,
            $this->instoreConfigMock,
            $this->storeManagerMock
        );
    }

    public function testAfterGetEmail()
    {
        $subjectMock = $this->createMock(QuoteAddress::class);
        $subjectMock->expects($this->any())
            ->method('getData')
            ->with(QuoteAddress::KEY_EMAIL)
            ->willReturn(null);
        $subjectMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteModel);
        $this->quoteModel->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('test@example.com');
        $subjectMock->expects($this->any())
            ->method('setEmail')
            ->with('test@example.com');

        $result = $this->addressPlugin->afterGetEmail($subjectMock, 'test@example.com');

        $this->assertEquals('test@example.com', $result);
    }

    public function testAfterGetBaseSubtotalWithDiscount()
    {
        $subjectMock = $this->getMockBuilder(QuoteAddress::class)
        ->setMethods(['getBaseSubtotal','getBaseDiscountAmount'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $subjectMock->expects($this->any())
            ->method('getBaseSubtotal')
            ->willReturn(100);
        $subjectMock->expects($this->any())
            ->method('getBaseDiscountAmount')
            ->willReturn(10);

        $result = $this->addressPlugin->afterGetBaseSubtotalWithDiscount($subjectMock, 110);

        $this->assertEquals(110, $result);
    }

    public function testAfterValidateMinimumAmount()
    {
        $subjectMock = $this->getMockBuilder(QuoteAddress::class)
        ->setMethods(['getIsVirtual','getAddressType','getBaseTaxAmount','getBaseSubtotalWithDiscount','getBaseSubtotal'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->scopeConfigMock->expects($this->any())
            ->method('isSetFlag')
            ->willReturn(false);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn('test');

        $result = $this->addressPlugin->afterValidateMinimumAmount($subjectMock, false);

        $this->assertTrue($result);
    }

    public function testAfterValidateMinimumAmountElse()
    {
        $subjectMock = $this->getMockBuilder(QuoteAddress::class)
        ->setMethods(['getIsVirtual','getAddressType','getBaseTaxAmount','getBaseSubtotalWithDiscount','getBaseSubtotal'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->scopeConfigMock->expects($this->any())
            ->method('isSetFlag')
            ->willReturn(true);

        $subjectMock->expects($this->any())
            ->method('getIsVirtual')
            ->willReturn(true);

        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturn('test');

        $result = $this->addressPlugin->afterValidateMinimumAmount($subjectMock, false);

        $this->assertFalse($result);
    }

    public function testAfterGetTotals()
    {
        $subjectMock = $this->createMock(QuoteAddress::class);
        $subjectMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteModel);
        $this->totalsReaderMock->expects($this->any())
            ->method('fetch')
            ->willReturn([]);

        $subjectMock->expects($this->any())
            ->method('getData')
            ->willReturn([]);
        $subjectMock->expects($this->any())
            ->method('getAllItems')
            ->willReturn([]);

        $result = $this->addressPlugin->afterGetTotals($subjectMock, []);

        $this->assertEquals([], $result);
    }

    public function testAroundRequestShippingRates()
    {
        $subjectMock = $this->createMock(QuoteAddress::class);
        $subjectMock->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->quoteModel);

        $this->quoteModel->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);
        $this->storeManagerMock->expects($this->any())
        ->method('getStore')
        ->willReturn($this->storeMock);
        $itemMock = $this->createMock(AbstractItem::class);
        $proceed = function () {
            return false;
        };

        $requestMock = $this->createMock(\Magento\Quote\Model\Quote\Address\RateRequest::class);
        $this->rateRequestFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($requestMock);

        $rateResultMock = $this->getMockBuilder(\Magento\Shipping\Model\Rate\Result::class)->setMethods(['getResult','getAllRates'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->rateCollectorMock->expects($this->any())
            ->method('create')
            ->willReturnSelf();
        $this->rateCollectorMock->expects($this->any())
            ->method('collectRates')
            ->willReturn($rateResultMock);

        $rateResultMock->expects($this->any())
            ->method('getResult')
            ->willReturn([]);

        $rateResultMock->expects($this->any())
            ->method('getAllRates')
            ->willReturn([]);

        $result = $this->addressPlugin->aroundRequestShippingRates($subjectMock, $proceed, $itemMock);

        $this->assertFalse($result);
    }

    public function testAfterCollectShippingRates()
    {
        $subjectMock = $this->getMockBuilder(QuoteAddress::class)
        ->setMethods(['setCollectShippingRates','removeAllShippingRates','requestShippingRates','getShippingRatesCollection'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->sessionManagerInterfaceMock->expects($this->any())
            ->method('start');
        $this->sessionManagerInterfaceMock->expects($this->any())
            ->method('getData')
            ->with('admin_quote_view')
            ->willReturn(false);

        $subjectMock->expects($this->any())
            ->method('setCollectShippingRates')
            ->with(false);
        $subjectMock->expects($this->any())
            ->method('removeAllShippingRates');

        $subjectMock->expects($this->any())
            ->method('requestShippingRates')
            ->willReturn(false);

        $this->quoteHelperMock->expects($this->any())
            ->method('isMiraklQuote')
            ->willReturn(false);

        $subjectMock->expects($this->any())
            ->method('getShippingRatesCollection')
            ->willReturn([]);

        $result = $this->addressPlugin->afterCollectShippingRates($subjectMock, $subjectMock);

        $this->assertEquals($subjectMock, $result);
    }

    public function testAfterGetStreet()
    {
        $street = ['Test Street','Test Street1'];
        $subjectMock = $this->createMock(QuoteAddress::class);
        $subjectMock->expects($this->any())
            ->method('getData')
            ->with('street')
            ->willReturn($street);

        $result = $this->addressPlugin->afterGetStreet($subjectMock, $street);

        $this->assertEquals($street, $result);
    }

}
