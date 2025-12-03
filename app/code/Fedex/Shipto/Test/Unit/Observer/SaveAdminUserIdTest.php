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
use Magento\User\Model\User;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;
use Magento\Catalog\Model\Product;
use Fedex\Shipto\Observer\SaveAdminUserId;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SaveAdminUserIdTest extends TestCase
{
    protected $authSession;
    protected $userMock;
    protected $productMock;
    protected $observerMock;
    protected $toggleConfig;
    protected $observer;
    protected function setUp(): void
    {
        $this->authSession = $this->getMockBuilder(Session::class)
        ->disableOriginalConstructor()
        ->setMethods(['getUser','isLoggedIn'])
        ->getMock();
        
        $this->userMock = $this->getMockBuilder(User::class)
        ->disableOriginalConstructor()
        ->setMethods(['getId'])
        ->getMock();
        
        $this->productMock = $this->getMockBuilder(Product::class)
        ->disableOriginalConstructor()
        ->setMethods(['setAdminUserId'])
        ->getMock();
        
        $this->observerMock = $this->getMockBuilder(Observer::class)
        ->disableOriginalConstructor()
        ->setMethods(['getProduct'])
        ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['getToggleConfigValue'])
        ->getMock();
        
        $objectManagerHelper = new ObjectManager($this);

        $this->observer = $objectManagerHelper->getObject(
            SaveAdminUserId::class,
            [
                'authSession' => $this->authSession,
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }
    
    public function testExecute()
    {
        
        $this->observerMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);
        
        $this->authSession->expects($this->any())
            ->method('isLoggedIn')
            ->willReturn(1);

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(1);

        $this->authSession->expects($this->any())
            ->method('getUser')
            ->willReturn($this->userMock);
            
        $this->userMock->expects($this->any())
            ->method('getId')
            ->willReturn(2);
            
        $this->productMock
            ->expects($this->once())
            ->method('setAdminUserId')
            ->willReturnSelf();
           
        $this->assertEquals($this->observer,$this->observer->execute($this->observerMock));
    }
}
