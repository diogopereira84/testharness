<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Orderhistory\Block\Order\Link;

class LinkTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\App\DefaultPathInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $defaultPathMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    /**
     * @var (\Magento\Framework\App\RequestInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $requestMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $LinkMock;
    /**
     * @var \Fedex\Orderhistory\Helper\Data $helperDataMock
     */
    protected $helperDataMock;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        $this->contextMock = $this
            ->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->defaultPathMock = $this
            ->getMockBuilder(\Magento\Framework\App\DefaultPathInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->registryMock = $this
            ->getMockBuilder(\Magento\Framework\Registry::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperDataMock = $this
            ->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled','isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->requestMock = $this
            ->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['getModuleName','getControllerName','getActionName','getPathInfo'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->LinkMock = $this->objectManager->getObject(
            Link::class,
            [
                'context' => $this->contextMock,
                'defaultPath' => $this->defaultPathMock,
                'registry' => $this->registryMock,
                'helper' => $this->helperDataMock,
                '_urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                '_request'=> $this->requestMock
            ]
        );
        $this->LinkMock->setKey('Invoices');
    }
    
    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Order\Link::class,
            '_toHtml',
        );

        $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->helperDataMock->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->LinkMock);
        $this->assertEquals('', $expectedResult);
    }
}
