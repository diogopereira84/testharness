<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Plugin\Quote\View\Totals;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Plugin\Quote\View\Totals\NegotiationPlugin;
use Magento\NegotiableQuote\Block\Adminhtml\Quote\View\Totals\Negotiation;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Framework\DataObject;

class NegotiationPluginTest extends TestCase
{
    /**
     * @var RequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestMock;

    /**
     * @var CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var NegotiationPlugin
     */
    private $plugin;

     /**
      * @var CartInterface
      */
    private $quoteMock;

    protected function setUp(): void
    {
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->quoteMock = $this->getMockBuilder(CartInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getDiscount'])
            ->getMockForAbstractClass();

        $this->plugin = new NegotiationPlugin(
            $this->requestMock,
            $this->quoteRepositoryMock
        );
    }

    public function testGetQuoteId(): void
    {
        $quoteId = 123;

        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $this->assertSame($quoteId, $this->plugin->getQuoteId());
    }

    public function testAfterGetTotalOptions(): void
    {
        $quoteId = 123;
        $discount = 50.00;

        $this->requestMock
            ->method('getParam')
            ->with('quote_id')
            ->willReturn($quoteId);

        $this->quoteMock
            ->expects($this->once())
            ->method('getDiscount')
            ->willReturn($discount);

        $this->quoteRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with($quoteId)
            ->willReturn($this->quoteMock);

        $subjectMock = $this->createMock(Negotiation::class);
        $result = [];

        $modifiedResult = $this->plugin->afterGetTotalOptions($subjectMock, $result);

        $this->assertArrayHasKey('amount', $modifiedResult);
        $this->assertInstanceOf(DataObject::class, $modifiedResult['amount']);
        $this->assertEquals(__('Amount Discount'), $modifiedResult['amount']->getData('label'));
        $this->assertTrue($modifiedResult['amount']->getData('is_price'));
        $this->assertEquals($discount, $modifiedResult['amount']->getData('value'));
    }
}
