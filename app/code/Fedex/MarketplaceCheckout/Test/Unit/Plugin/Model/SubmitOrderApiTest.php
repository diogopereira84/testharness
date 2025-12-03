<?php

declare(strict_types=1);

namespace Fedex\MarketplaceCheckout\Test\Unit\Plugin\Model;

use Fedex\MarketplaceCheckout\Plugin\Model\SubmitOrderApi;
use Fedex\MarketplaceCheckout\Model\QuoteOptions;
use Fedex\MarketplaceProduct\Helper\Quote as QuoteHelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as Subject;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class SubmitOrderApiTest extends TestCase
{
    /**
     * @var QuoteOptions|MockObject
     */
    private $quoteOptionsMock;

    /**
     * @var QuoteHelper|MockObject
     */
    private $quoteHelperMock;

    /**
     * @var SubmitOrderApi
     */
    private $plugin;

    /**
     * @var Subject|MockObject
     */
    private $subjectMock;

    /**
     * @var DataObject|MockObject
     */
    private $dataObjectMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    protected function setUp(): void
    {
        $this->quoteOptionsMock = $this->createMock(QuoteOptions::class);
        $this->quoteHelperMock = $this->createMock(QuoteHelper::class);
        $this->subjectMock = $this->createMock(Subject::class);

        $this->dataObjectMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getQuoteData'])
            ->getMock();

        $this->quoteMock = $this->createMock(Quote::class);

        $this->plugin = new SubmitOrderApi(
            $this->quoteOptionsMock,
            $this->quoteHelperMock
        );
    }

    /**
     * Test beforeCreateOrderBeforePayment when quote is a Mirakl quote
     */
    public function testBeforeCreateOrderBeforePaymentWithMiraklQuote(): void
    {
        $paymentData = ['some' => 'payment_data'];

        $this->dataObjectMock->expects($this->once())
            ->method('getQuoteData')
            ->willReturn($this->quoteMock);

        $this->quoteHelperMock->expects($this->once())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(true);

        $this->quoteOptionsMock->expects($this->once())
            ->method('setMktShippingAndTaxInfo')
            ->with($this->quoteMock);

        $result = $this->plugin->beforeCreateOrderBeforePayment(
            $this->subjectMock,
            $paymentData,
            $this->dataObjectMock
        );

        $this->assertNull($result, 'Plugin beforeCreateOrderBeforePayment should not return a value');
        $this->assertTrue(true, 'Plugin executed without throwing exceptions');
    }

    /**
     * Test beforeCreateOrderBeforePayment when quote is not a Mirakl quote
     */
    public function testBeforeCreateOrderBeforePaymentWithNonMiraklQuote(): void
    {
        $paymentData = ['some' => 'payment_data'];

        $this->dataObjectMock->expects($this->once())
            ->method('getQuoteData')
            ->willReturn($this->quoteMock);

        $this->quoteHelperMock->expects($this->once())
            ->method('isMiraklQuote')
            ->with($this->quoteMock)
            ->willReturn(false);

        $this->quoteOptionsMock->expects($this->never())
            ->method('setMktShippingAndTaxInfo');

        $result = $this->plugin->beforeCreateOrderBeforePayment(
            $this->subjectMock,
            $paymentData,
            $this->dataObjectMock
        );

        $this->assertNull($result, 'Plugin beforeCreateOrderBeforePayment should not return a value');
        $this->assertTrue(true, 'Plugin executed without throwing exceptions');
    }
}
