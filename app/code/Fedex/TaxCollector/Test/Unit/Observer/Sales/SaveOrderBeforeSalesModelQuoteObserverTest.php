<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\TaxCollector\Test\Unit\Observer\Sales;

use Fedex\TaxCollector\Observer\Sales\SaveOrderBeforeSalesModelQuoteObserver;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Helper\Data as DeliveryData;
use Magento\Framework\Exception;
use Magento\Framework\Phrase;
use Magento\Framework\Exception\LocalizedException;


/**
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SaveOrderBeforeSalesModelQuoteObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var (\Magento\Framework\DataObject\Copy & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $copyMock;
    protected $observerMock;
    protected $eventMock;
    protected $quoteMock;
    protected $orderMock;
    /**
     * @var (\Magento\Quote\Model\Quote\Item & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteitemMock;
    protected $dataobjectMock;
    protected $Item;
    protected $toggleConfig;
    protected $helper;
    protected $SaveOrderBeforeSalesModelQuoteObserverTestobj;
    protected function setUp(): void
    {
        $this->copyMock = $this->getMockBuilder(\Magento\Framework\DataObject\Copy::class)
            ->disableOriginalConstructor()
                ->getMock();

        $this->observerMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
        ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
                ->getMock();

        $this->eventMock = $this->getMockBuilder(\Magento\Framework\Event::class)
        ->setMethods(['getData'])
            ->disableOriginalConstructor()
                ->getMock();
        $this->quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
        ->setMethods(['getDiscount','getCustomTaxAmount','getProductionLocationId','getAllVisibleItems'])
            ->disableOriginalConstructor()
                ->getMock();

        $this->orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
        ->setMethods(['getItemByQuoteItemId'])
            ->disableOriginalConstructor()
                ->getMock();

                
        $this->quoteitemMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
                    ->getMock();
                    
        $this->dataobjectMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
        ->setMethods(['setDiscountAmount','setBaseDiscountAmount'])
                    ->disableOriginalConstructor()
                            ->getMock();

        $this->Item = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
        ->setMethods(['getOptionByCode','getProduct','setIsSuperMode', 'save'])
        ->disableOriginalConstructor()
        ->getMock();

        $this->toggleConfig = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['getToggleConfigValue'])
        ->getMock();

        $this->helper = $this->getMockBuilder(DeliveryData::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->SaveOrderBeforeSalesModelQuoteObserverTestobj = $objectManagerHelper->getObject(
            SaveOrderBeforeSalesModelQuoteObserver::class,
            [
                'copyMock' => $this->copyMock,
                'observerMock' => $this->observerMock,
                'eventMock' => $this->eventMock,
                'toggleConfig' => $this->toggleConfig,
                'helper' => $this->helper

            ]
        );
    }

    public function testExecute()
    {
        $this->observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($this->eventMock,$this->eventMock);
		$this->eventMock->expects($this->exactly(2))->method('getData')->withConsecutive(['order'],['quote'])->willReturn($this->orderMock,$this->quoteMock);
        $this->quoteMock->expects($this->any())->method('getDiscount')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getCustomTaxAmount')->willReturn(123);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->helper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->quoteMock->expects($this->any())->method('getProductionLocationId')->willReturn(123);
        $this->quoteMock->expects($this->any())->method('getAllVisibleItems')->willReturn([$this->Item]);
        $this->orderMock->expects($this->any())->method('getItemByQuoteItemId')->willReturn($this->dataobjectMock);
        $this->dataobjectMock->expects($this->any())->method('setDiscountAmount')->willReturn($this->dataobjectMock);
		
        $this->assertNotNull($this->SaveOrderBeforeSalesModelQuoteObserverTestobj->execute($this->observerMock));
    }

    public function testExecuteWithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->observerMock->expects($this->any())->method('getEvent')->willThrowException($exception);
        $this->assertNotNull($this->SaveOrderBeforeSalesModelQuoteObserverTestobj->execute($this->observerMock));
    }
	
}

