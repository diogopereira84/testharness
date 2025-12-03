<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\Orderhistory\Block\Quote\Customerinfo;

class CustomerinfoTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $quoteFactory;
    protected $orderHelper;
    protected $requestMock;
    protected $quoteMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerinfoObj;
    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

            $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        
        $this->orderHelper = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled', 'isEnhancementEnabledForPrint'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customerinfoObj = $this->objectManager->getObject(
            Customerinfo::class,
            [
                'context' => $this->contextMock,
                'orderHelper' => $this->orderHelper,
                'quoteFactory'=>$this->quoteFactory,
                '_request' => $this->requestMock
            ]
        );
    }
    
    /**
     * Assert getOrderviewbreadcrumbs When Module is enambled.
     *
     */
    public function testGetCustomerDetails()
    {
        $this->requestMock->expects($this->once())->method('getParam')->willReturn(77);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->assertEquals($this->quoteMock, $this->customerinfoObj->getCustomerDetails());
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetQuote()
    {
        $this->requestMock->expects($this->once())->method('getParam')->willReturn(77);
        $this->quoteFactory->expects($this->any())->method('create')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->any())->method('load')->willReturnSelf();
        $this->assertEquals($this->quoteMock, $this->customerinfoObj->getQuote());
        
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetFormattedPhone()
    {
        $telephone = '4003000500';
        $output = '(400) 300-0500';
        $this->assertEquals($output, $this->customerinfoObj->getFormattedPhone($telephone));
    }

    /**
     * @inheritDoc
     *
     */
    public function testIsEnhancementEnabledForPrint()
    {
        $this->orderHelper->expects($this->any())->method('isEnhancementEnabledForPrint')->willReturn(true);
        $this->assertTrue($this->customerinfoObj->isEnhancementEnabledForPrint());
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetDeliveryMethodName()
    {
        $shippingMethod = 'fedexshipping_LOCAL_DELIVERY_PM';
        $shippingDescription = 'FedEx LOCAL DELIVERY PM - FedEx Local Delivery - Tuesday, May 9, 5:00pm';
        $output = 'FedEx Local Delivery';
        $this->assertEquals(
            $output,
            $this->customerinfoObj->getDeliveryMethodName($shippingMethod, $shippingDescription)
        );
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetDeliveryMethodNameElseIf()
    {
        $shippingMethod = 'fedexshipping_LOCAL_DELIVERY_PM';
        $shippingDescription = 'FedEx Local Delivery - Tuesday, May 9, 5:00pm';
        $output = 'FedEx Local Delivery';
        $this->assertEquals(
            $output,
            $this->customerinfoObj->getDeliveryMethodName($shippingMethod, $shippingDescription)
        );
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetEstimatedShippingDelivery()
    {
        $shippingDescription = 'FedEx Local Delivery - FedEx Local Delivery - Tuesday, October 27, 05:00pm';
        $output = 'Tuesday, October 27, 05:00pm';
        $this->assertEquals($output, $this->customerinfoObj->getEstimatedShippingDelivery($shippingDescription));
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetEstimatedShippingDeliveryElseIf1()
    {
        $shippingDescription = 'FedEx Local Delivery - Tuesday, October 27, 05:00pm';
        $output = 'Tuesday, October 27, 05:00pm';
        $this->assertEquals($output, $this->customerinfoObj->getEstimatedShippingDelivery($shippingDescription));
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetEstimatedShippingDeliveryElseIf2()
    {
        $shippingDescription = 'FedEx Local Delivery - FedEx Local Delivery - Tuesday, October 27 05:00 pm';
        $output = 'Tuesday, October 27, 05:00pm';
        $this->assertEquals($output, $this->customerinfoObj->getEstimatedShippingDelivery($shippingDescription));
    }

    /**
     * @inheritDoc
     *
     */
    public function testGetEstimatedShippingDeliveryElseIf3()
    {
        $shippingDescription = 'FedEx Local Delivery - Tuesday, October 27 05:00 pm';
        $output = 'Tuesday, October 27, 05:00pm';
        $this->assertEquals($output, $this->customerinfoObj->getEstimatedShippingDelivery($shippingDescription));
    }
}
