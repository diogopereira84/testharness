<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Orderhistory\Block\Order\Info;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Framework\View\Page\Title;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\InfoInterface;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $infoInterface;
    protected $template;
    protected $pageConfig;
    protected $pageTitleMock;
    protected $registry;
    /**
     * @var (\Magento\Framework\View\LayoutInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $Layout;
    /**
     * @var (\Magento\Framework\View\Element\AbstractBlock & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $AbstractBlock;
    protected $order;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $InfoMock;
    /**
     * @var string
     */
    private $_template = 'Magento_Sales::order/info.phtml';

    /**
     * @var \Fedex\Orderhistory\Helper\Data $helperDataMock
     */
    protected $helperDataMock;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->setMethods(['escapeHtml', 'registry'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperDataMock = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled', 'isEnhancementEnabeled', 'isPrintReceiptRetail', 'getContactAddress'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentHelper = $this->getMockBuilder(PaymentHelper::class)
            ->setMethods(['getInfoBlock'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->infoInterface = $this->getMockBuilder(InfoInterface::class)
            ->getMockForAbstractClass();
        $this->template = $this->getMockBuilder(Template::class)
            ->setMethods(['payment_info'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageConfig = $this->getMockBuilder(\Magento\Framework\View\Page\Config::class)
            ->setMethods(['getTitle'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->setMethods(['set'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder(Registry::class)
            ->setMethods(['registry'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->Layout = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->AbstractBlock = $this->getMockBuilder(\Magento\Framework\View\Element\AbstractBlock::class)
            ->disableOriginalConstructor()
            ->setMethods(['getLayout'])
            ->getMockForAbstractClass();
        $this->order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment','getEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager = new ObjectManager($this);
        $this->InfoMock = $this->objectManager->getObject(
            Info::class,
            [
                'context' => $this->contextMock,
                'paymentHelper' => $this->paymentHelper,
                'pageConfig' => $this->pageConfig,
                'title' => $this->pageTitleMock,
                'registry'=> $this->registry,
                '_layout' => $this->Layout,
                'info' => $this->infoInterface,
                'helper' => $this->helperDataMock,
            ]
        );
    }
    
    /**
     * Assert _prepareLayout.
     *
     * @return string
     */
    public function testPrepareLayout()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            '_prepareLayout',
        );
        $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helperDataMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(true);
        $this->contextMock->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())->method('set')->willReturn('test');
        $this->pageTitleMock->expects($this->any())->method('set')->willReturn('test');

        $order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment','getEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registry->method('registry')->withConsecutive(['current_order'], ['current_order'])
        ->willReturnOnConsecutiveCalls($order, $order);

        $order->expects($this->any())->method('getPayment')->willReturn($this->infoInterface);
        $this->paymentHelper->expects($this->any())->method('getInfoBlock')
        ->with($this->infoInterface)->willReturn($this->template);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->InfoMock);
        $this->assertNull($expectedResult);
    }

    /**
     * Assert _prepareLayout when disabled.
     *
     * @return string
     */
    public function testPrepareLayoutdisabled()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            '_prepareLayout',
        );
        $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $this->helperDataMock->expects($this->any())->method('isEnhancementEnabeled')->willReturn(false);
        $this->contextMock->expects($this->any())->method('getPageConfig')->willReturn($this->pageConfig);
        $this->pageConfig->expects($this->any())->method('getTitle')->willReturn($this->pageTitleMock);
        $this->pageTitleMock->expects($this->any())->method('set')->willReturn('test');
        $this->pageTitleMock->expects($this->any())->method('set')->willReturn('test');
        $order = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['getRealOrderId','getPayment','getEstimatedPickupTime'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->registry->method('registry')->withConsecutive(['current_order'], ['current_order'])
        ->willReturnOnConsecutiveCalls($order, $order);
        $order->expects($this->any())->method('getPayment')->willReturn($this->infoInterface);
        $this->paymentHelper->expects($this->any())->method('getInfoBlock')
        ->with($this->infoInterface)->willReturn($this->template);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->InfoMock);
        $this->assertNull($expectedResult);
    }

    /**
     * Assert getTemplate.
     *
     * @return bool
     */
    public function testGetTemplate()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            'getTemplate',
        );
        $testinMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            'isPrintReceiptRetail',
        );
        $this->helperDataMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(false);
        $inexpectedResult = $testinMethod->invoke($this->InfoMock);
        $this->assertFalse($inexpectedResult);
        $expectedResult = $testMethod->invoke($this->InfoMock);
        $this->assertEquals($this->_template, $expectedResult);
    }

    /**
     * Assert getTemplate with isPrintReceiptRetail true.
     *
     * @return bool
     */
    public function testGetTemplateWithIf()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            'getTemplate',
        );
        $testinMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Info::class,
            'isPrintReceiptRetail',
        );
        $this->helperDataMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $inexpectedResult = $testinMethod->invoke($this->InfoMock);
        $this->assertTrue($inexpectedResult);
        $expectedResult = $testMethod->invoke($this->InfoMock);
        $this->assertEquals('', $expectedResult);
    }

    /**
     * Assert testGetEstimatedPickUpDateTime
     */
    public function testGetEstimatedPickUpDateTime()
    {
        $pickUpDateTimeFormat = 'Monday, April 24, 4:00pm';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals($pickUpDateTimeFormat, $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetEstimatedPickUpDateTime1
     */
    public function testGetEstimatedPickUpDateTime1()
    {
        $pickUpDateTimeFormat = 'Thursday, February 16th, 2023 at 5:00 p.m.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Thursday, February 16, 5:00pm', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetEstimatedPickUpDateTimeAM
     */
    public function testGetEstimatedPickUpDateTimeAM()
    {
        $pickUpDateTimeFormat = 'Thursday, February 16th, 2023 at 5:00 a.m.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Thursday, February 16, 5:00am', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetEstimatedPickUpDateTime2
     */
    public function testGetEstimatedPickUpDateTime2()
    {
        $pickUpDateTimeFormat = 'Tuesday,April 25th At 08:00 AM';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Tuesday, April 25, 08:00am', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetSelectedShippingMethodDate6
     */
    public function testGetEstimatedPickUpDateTime3()
    {
        $pickUpDateTimeFormat = 'Monday,  April 17th 5:00 P.M.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Monday, April 17, 5:00pm', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetSelectedShippingMethodDate5
     */
    public function testGetEstimatedPickUpDateTime5()
    {
        $pickUpDateTimeFormat = 'Monday,  April 17th 5:00 A.M.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Monday, April 17, 5:00am', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetEstimatedPickUpDateTime4
     */
    public function testGetEstimatedPickUpDateTime4()
    {
        $pickUpDateTimeFormat = 'Thursday,  March 9th</br>7:00 P.M.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Thursday, March 9, 7:00pm', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * Assert testGetEstimatedPickUpDateTime6
     */
    public function testGetEstimatedPickUpDateTime6()
    {
        $pickUpDateTimeFormat = 'Thursday,  March 9th</br>7:00 A.M.';
        $this->registry->expects($this->any())->method('registry')->willReturn($this->order);
        $this->order->expects($this->any())->method('getEstimatedPickupTime')->willReturn($pickUpDateTimeFormat);
        $this->assertEquals('Thursday, March 9, 7:00am', $this->InfoMock->getEstimatedPickUpDateTime());
    }

    /**
     * @inheritDoc
     */
    public function testGetContactPhone()
    {
        $output = '(456) 300-0500';
        $address = ['name'=>'abc abc','email'=>'abc@abc.com','telephone'=>'4563000500'];
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperDataMock->expects($this->any())->method('getContactAddress')->willReturn($address);
        $this->registry->expects($this->any())->method('registry')->willReturn($order);
        $order->expects($this->any())->method('getQuoteId')->willReturn(1234);
        $expectedResult = $this->InfoMock->getContactPhone();
        $this->assertEquals($output, $expectedResult);
    }

    /**
     * @inheritDoc
     */
    public function testGetContactPhoneWithoutTelephone()
    {
        $address = ['name'=>'abc abc','email'=>'abc@abc.com','telephone'=>''];
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->setMethods(['getQuoteId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->helperDataMock->expects($this->any())->method('getContactAddress')->willReturn($address);
        $this->registry->expects($this->any())->method('registry')->willReturn($order);
        $order->expects($this->any())->method('getQuoteId')->willReturn(1234);
        $expectedResult = $this->InfoMock->getContactPhone();
        $this->assertEquals('', $expectedResult);
    }
}
