<?php

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote\Info;

use Magento\NegotiableQuote\Block\Quote\Info\Order;
use Fedex\Orderhistory\Helper\Data;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Quote\Info\Order as PluginOrder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class OrderTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var (\Magento\Framework\App\Request\Http & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $http;
    protected $pluginFilterMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $filters;
    /**
     * @var \Fedex\Orderhistory\Helper\Data $helper
     */
    protected $helper;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
                                        ->setMethods(['isModuleEnabled'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->http = $this->getMockBuilder(Http::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->pluginFilterMock = $this->getMockBuilder(Order::class)
                                        ->setMethods(['setTemplate'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->filters = $this->objectManager->getObject(
            PluginOrder::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * testBeforeToHtml
     */
    public function testBeforeToHtml()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $this->pluginFilterMock->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->assertEquals(null, $this->filters->beforeToHtml($this->pluginFilterMock));
    }
}
