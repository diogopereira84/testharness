<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Orderhistory\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Registry;
use Fedex\Orderhistory\Block\Order\PrintShipment;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;

class PrintShipmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $printShipmentMock;
    /**
     * @var retailTemplate
     */
    protected $_retailTemplate = 'Fedex_Orderhistory::order/retail-print.phtml';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistryMock = null;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelperMock;

    /**
     * @var AddressRenderer
     */
    protected $addressRendererMock;

    /**
     * @var \Fedex\Orderhistory\Helper\Data
     */
    protected $orderHistoryDataHelperMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(TemplateContext::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
        ->setMethods(['registry'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->orderHistoryDataHelperMock = $this->getMockBuilder(OrderHistoryHelper::class)
        ->setMethods(['isPrintReceiptRetail'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->paymentHelperMock = $this
        ->getMockBuilder(PaymentHelper::class)
        ->setMethods(['getInfoBlock'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->addressRendererMock = $this->getMockBuilder(AddressRenderer::class)
        ->disableOriginalConstructor()
        ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->printShipmentMock = $this->objectManager->getObject(
            PrintShipment::class,
            [
                'context' => $this->contextMock,
                '_coreRegistry' => $this->coreRegistryMock,
                'paymentHelper' => $this->paymentHelperMock,
                'addressRenderer' => $this->addressRendererMock,
                'orderHistoryDataHelper' => $this->orderHistoryDataHelperMock,
                'data' => []
            ]
        );
    } //end Setup

    /**
     * Test case for get Template
     */
    public function testgetTemplate()
    {
        $testMethod = new \ReflectionMethod(PrintShipment::class, 'getTemplate');
        $this->orderHistoryDataHelperMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $expectedResult = $testMethod->invoke($this->printShipmentMock);
        $this->assertEquals('', $expectedResult);
    }//end testgetTemplate
}
