<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Test\Unit\Block\Adminhtml\Quote\View;

use PHPUnit\Framework\TestCase;
use Fedex\UploadToQuote\Block\Adminhtml\Quote\View\SpecialInstructions;
use Magento\Backend\Block\Template\Context;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Framework\App\Request\Http;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class SpecialInstructionsTest extends TestCase
{

    protected $quoteMock;
    protected $specialInstructionsMock;
    /** @var Context|MockObject */
    private $contextMock;

    /** @var CartRepositoryInterface|MockObject */
    private $quoteRepositoryMock;

    /** @var AdminConfigHelper|MockObject */
    private $adminConfigHelperMock;

    /** @var Http|MockObject */
    private $requestMock;

    /**
     * Setup method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->adminConfigHelperMock = $this->createMock(AdminConfigHelper::class);

        $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->specialInstructionsMock = $objectManagerHelper->getObject(
            SpecialInstructions::class,
            [
                'context' => $this->contextMock,
                'quoteRepository' => $this->quoteRepositoryMock,
                'adminConfigHelper' => $this->adminConfigHelperMock
            ]
        );
    }

    /**
     * Test method  testGetQuoteIdReturnsQuoteId
     *
     * @return void
     */
    public function testGetQuoteIdReturnsQuoteId()
    {
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->willReturn(123);
        $result = $this->specialInstructionsMock->getQuoteId();

        $this->assertEquals(123, $result);
    }

    /**
     * Test method  testGetQuoteIdReturnsNullOnException
     *
     * @return void
     */
    public function testGetQuoteIdReturnsNullOnException()
    {
        $this->requestMock->method('getParam')->willThrowException(new NoSuchEntityException());

        $result = $this->specialInstructionsMock->getQuoteId();
        $this->assertNull($result);
    }

    /**
     * Test method  testGetSpecialInstructionsReturnsData
     *
     * @return void
     */
    public function testGetSpecialInstructionsReturnsData()
    {
        $quoteId = 123;
        $this->requestMock->method('getParam')->with('quote_id')->willReturn($quoteId);

        $itemMock = $this->createMock(CartItemInterface::class);

        $this->quoteRepositoryMock->method('get')->with($quoteId)->willReturn($this->quoteMock);
        $this->quoteMock->method('getAllVisibleItems')->willReturn([$itemMock]);

        $itemMock->method('getName')->willReturn('Test Product');
        $itemMock->method('getSku')->willReturn('test-sku');
        $this->adminConfigHelperMock->method('getProductJson')->willReturn('{}');
        $this->adminConfigHelperMock->method('isProductLineItems')->willReturn('Special Instructions');

        $result = $this->specialInstructionsMock->getSpecialInstructions();
        $expected = [
            [
                'title' => 'Test Product',
                'sku' => 'test-sku',
                'details' => 'Special Instructions',
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test method  testGetSpecialInstructionsReturnsData
     *
     * @return void
     */
    public function testGetSpecialInstructionsReturnsEmptyOnNoQuoteId()
    {
        $this->requestMock->method('getParam')->with('quote_id')->willReturn(null);

        $result = $this->specialInstructionsMock->getSpecialInstructions();
        $this->assertEquals([], $result);
    }

    /**
     * Test method  testGetSpecialInstructionsReturnsData
     *
     * @return void
     */
    public function testIsEnhancementToggleEnabledReturnsTrue()
    {
        $this->adminConfigHelperMock->method('isMagentoQuoteDetailEnhancementToggleEnabled')->willReturn(true);

        $result = $this->specialInstructionsMock->isEnhancementToggleEnabled();
        $this->assertTrue($result);
    }
    
    /**
     * Test method  testIsEnhancementToggleEnabledReturnsFalse
     *
     * @return void
     */
    public function testIsEnhancementToggleEnabledReturnsFalse()
    {
        $this->adminConfigHelperMock->method('isMagentoQuoteDetailEnhancementToggleEnabled')->willReturn(false);

        $result = $this->specialInstructionsMock->isEnhancementToggleEnabled();
        $this->assertFalse($result);
    }
}
