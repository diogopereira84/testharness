<?php
namespace Fedex\Orderhistory\Test\Unit\Block\Link;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Fedex\Orderhistory\Block\Link\NegotiableQuote;

class NegotiableQuoteTest extends \PHPUnit\Framework\TestCase
{
   /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $negotiableQuoteMock;
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
        
        $this->helperDataMock = $this
            ->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->setMethods(['isModuleEnabled'])
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

        $this->objectManager = new ObjectManager($this);
        $this->negotiableQuoteMock = $this->objectManager->getObject(
            NegotiableQuote::class,
            [
                'context' => $this->contextMock,
                '_urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'helperData' => $this->helperDataMock
            ]
        );
    }
    
    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Link\NegotiableQuote::class,
            '_toHtml',
        );

        $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->negotiableQuoteMock);
        $this->assertIsString($expectedResult);
    }
    
    /**
     * Assert _toHtml in Negative case
     *
     * @return ''
     */
    public function testToHtmlWhenModuleDisable()
    {
        $testMethod = new \ReflectionMethod(
            \Fedex\Orderhistory\Block\Link\NegotiableQuote::class,
            '_toHtml',
        );

        $this->helperDataMock->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->negotiableQuoteMock);
        $this->assertEquals('', $expectedResult);
    }
}
