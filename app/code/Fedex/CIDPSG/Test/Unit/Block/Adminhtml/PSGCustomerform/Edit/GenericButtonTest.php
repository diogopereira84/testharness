<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Block\Adminhtml\PSGCustomerform\Edit;

use PHPUnit\Framework\TestCase;
use Magento\Backend\Block\Widget\Context;
use Fedex\CIDPSG\Block\Adminhtml\PSGCustomerform\Edit\GenericButton;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Url;

/**
 * GenericButtonTest unit test class
 */
class GenericButtonTest extends TestCase
{
    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var RequestInterface $requestMock;
     */
    protected $requestMock;
    
    /**
     * @var UrlInterface $urlBuilderMock;
     */
    protected $urlBuilderMock;

    /**
     * @var GenericButton $genericButton;
     */
    protected $genericButton;
    
    /**
     * Set up method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->urlBuilderMock = $this->createMock(UrlInterface::class);
        $this->contextMock->method('getRequest')->willReturn($this->requestMock);
        $this->contextMock->method('getUrlBuilder')->willReturn($this->urlBuilderMock);
        $this->genericButton = $this->getMockForAbstractClass(GenericButton::class, [$this->contextMock]);
    }

    /**
     * Test testGetModelId
     *
     * @return void
     */
    public function testGetModelId()
    {
        $this->requestMock->method('getParam')->with('id')->willReturn(123);
        $modelId = $this->genericButton->getModelId();

        $this->assertEquals(123, $modelId);
    }
    
    /**
     * Test testGetUrl
     *
     * @return void
     */
    public function testGetUrl()
    {
        $route = 'myroute';
        $params = ['param1' => 'value1', 'param2' => 'value2'];
        $expectedUrl = 'http://example.com/myroute/param1/value1/param2/value2';
        $this->urlBuilderMock->method('getUrl')->with($route, $params)->willReturn($expectedUrl);
        $url = $this->genericButton->getUrl($route, $params);

        $this->assertEquals($expectedUrl, $url);
    }
}
