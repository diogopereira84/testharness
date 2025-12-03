<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Shipto\Test\Unit\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Catalog\Model\Product;
use Fedex\Shipto\Observer\LockAttribute;

class LockAttributeTest extends TestCase
{
    protected $productMock;
    protected $observerMock;
    protected $observer;
    protected function setUp(): void
    {
        $this->productMock = $this->getMockBuilder(Product::class)
        ->disableOriginalConstructor()
        ->setMethods(['lockAttribute'])
        ->getMock();
        
        $this->observerMock = $this->getMockBuilder(Observer::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProduct'])
        ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);

        $this->observer = $objectManagerHelper->getObject(
            LockAttribute::class,
            [
            ]
        );
    }
    public function testExecute()
    {
        
        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
              
        $this->productMock
            ->expects($this->once())
            ->method('lockAttribute')
            ->willReturnSelf();
           
        $this->observer->execute($this->observerMock);
    }
}
