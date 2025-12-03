<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Observer\Frontend\Catalog;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\Cart\Observer\Frontend\Catalog\RemoveQuoteItem;
use Magento\Framework\Event;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Framework\Event\Observer;
use Fedex\FXOPricing\Helper\FXORate;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Test class for RemoveQuoteItem
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class RemoveQuoteItemTest extends TestCase
{
    /**
     * @var RemoveQuoteItem
     */
    protected $observer;

    /**
     * @var ToggleConfig
     */
    protected $toggleConfig;

    /**
     * @var FXORateQuote|MockObject
     */
    protected $fxoRateQuoteMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var FXORate|MockObject
     */
    protected $removeQuoteItemobj;

    /**
     * @var FXORate
     */
    protected $fxoRateHelper;

    /**
     * @var MockObject
     */
    protected $eventMock;

    /**
     * @var MockObject
     */
    protected $quoteItemMock;

    /**
     * @var MockObject
     */
    protected $quote;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->fxoRateHelper = $this->getMockBuilder(FXORate::class)
            ->setMethods(['getFXORate', 'isEproCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getQuoteItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteItemMock = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getQuote', 'getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getQuote', 'getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->observer = $this->getMockBuilder(Observer::class)
            ->setMethods(['getEvent', 'getQuoteItem', 'getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);

        $this->objectManager = new ObjectManager($this);
        $this->removeQuoteItemobj = $this->objectManager->getObject(
            RemoveQuoteItem::class,
            [
                'fxoRateHelper' => $this->fxoRateHelper,
                'fxoRateQuote'  => $this->fxoRateQuoteMock,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getQuoteItem')->willReturn($this->quoteItemMock);
        $this->quoteItemMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->fxoRateHelper->expects($this->any())->method('getFXORate')->willReturnSelf();

        $this->assertEquals(null, $this->removeQuoteItemobj->execute($this->observer));
    }

    /**
     * Test execute function
     *
     * @return void
     */
    public function testExecuteWithToggle()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->observer->expects($this->any())->method('getEvent')->willReturn($this->eventMock);
        $this->eventMock->expects($this->any())->method('getQuoteItem')->willReturn($this->quoteItemMock);
        $this->quoteItemMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->fxoRateHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);
        $this->assertEquals(null, $this->removeQuoteItemobj->execute($this->observer));
    }

    /**
     * Test execEpro function
     *
     * @return void
     */
    public function testExecEpro(): void
    {
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuoteItem'])
            ->getMock();

        $item = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $quote = $this->createMock(\Magento\Framework\DataObject::class);

        $observer->method('getEvent')->willReturn($event);
        $event->method('getQuoteItem')->willReturn($item);
        $item->method('getQuote')->willReturn($quote);

        $this->fxoRateHelper
            ->expects($this->once())
            ->method('isEproCustomer')
            ->willReturn(true);

        $this->fxoRateHelper
            ->expects($this->once())
            ->method('getFXORate')
            ->with($quote);

        $this->fxoRateQuoteMock
            ->expects($this->never())
            ->method('getFXORateQuote');

        $this->assertNull(
            $this->removeQuoteItemobj->execute($observer),
            'execute() should return null'
        );
    }
    
}



