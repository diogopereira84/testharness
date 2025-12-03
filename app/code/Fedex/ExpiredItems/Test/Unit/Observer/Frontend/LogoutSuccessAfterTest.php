<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Observer\Frontend;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\ExpiredItems\Observer\Frontend\LogoutSuccessAfter;

/**
 * Test class LogoutSuccessAfter
 */
class LogoutSuccessAfterTest extends TestCase
{
    /**
     * @var ExpiredItem $expiredItemMock
     */
    private $expiredItemMock;

    /**
     * @var Observer
     */
    private $observerMock;

    /**
     * @var LogoutSuccessAfter
     */
    private $logoutSuccessAfterMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        
        $this->expiredItemMock = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['clearExpiredModalCookie'])
            ->getMock();
        
        $this->observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $objectManagerHelper = new ObjectManager($this);
        $this->logoutSuccessAfterMock = $objectManagerHelper->getObject(
            LogoutSuccessAfter::class,
            [
                'expiredItemHelper' => $this->expiredItemMock
            ]
        );
    }

    /**
     * Test for execute
     *
     * @return void
     */
    public function testExecute()
    {
        $this->expiredItemMock->expects($this->once())->method('clearExpiredModalCookie');
        
        $this->assertNull($this->logoutSuccessAfterMock->execute($this->observerMock));
    }
}
