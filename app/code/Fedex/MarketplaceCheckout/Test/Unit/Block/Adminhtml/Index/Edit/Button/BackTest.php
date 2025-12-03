<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Block\Adminhtml\Index\Edit\Button;

use Fedex\MarketplaceCheckout\Block\Adminhtml\Index\Edit\Button\Back;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class BackTest extends TestCase
{
    /**
     * @var Back|MockObject
     */
    private $backButton;

    /**
     * @var UrlInterface|MockObject
     */
    private $urlBuilderMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->urlBuilderMock = $this->getMockForAbstractClass(UrlInterface::class);

        $this->backButton = $this->getMockBuilder(Back::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        $this->backButton->method('getUrl')->willReturnCallback(function ($route) {
            return $this->urlBuilderMock->getUrl($route);
        });
    }

    /**
     * Test getButtonData method
     */
    public function testGetButtonData()
    {
        $expectedUrl = 'http://example.com/mirakl/shop/index';
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('mirakl/shop/index')
            ->willReturn($expectedUrl);

        $expected = [
            'label' => __('Back'),
            'on_click' => sprintf("location.href = '%s';", $expectedUrl),
            'class' => 'back',
            'sort_order' => 10,
        ];

        $this->assertEquals($expected, $this->backButton->getButtonData());
    }

    /**
     * Test getBackUrl method
     */
    public function testGetBackUrl()
    {
        $expectedUrl = 'http://example.com/mirakl/shop/index';
        $this->urlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('mirakl/shop/index')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->backButton->getBackUrl());
    }
}
