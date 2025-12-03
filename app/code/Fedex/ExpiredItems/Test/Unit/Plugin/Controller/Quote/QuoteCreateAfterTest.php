<?php
/**
 * Copyright © Fedex. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Plugin\Controller\Quote;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\ExpiredItems\Plugin\Controller\Quote\QuoteCreateAfter;
use PHPUnit\Framework\TestCase;
use Fedex\Delivery\Controller\Quote\Create;

/**
 * Test Plugin Class for QuoteCreateAfterTest
 */
class QuoteCreateAfterTest extends TestCase
{

    protected $subject;
    protected $quoteCreateAfterMock;
    /**
     * @var ExpiredItem $expiredItemHelperMock
     */
    private $expiredItemHelperMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->expiredItemHelperMock = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['clearExpiredModalCookie'])
            ->getMock();
        
        $this->subject = $this->getMockBuilder(Create::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->quoteCreateAfterMock = $objectManagerHelper->getObject(
            QuoteCreateAfter::class,
            [
                'expiredItemHelper' => $this->expiredItemHelperMock
            ]
        );
    }

    /**
     * Test method for afterCheckoutSaveAddressAndClearSession
     *
     * @return void
     */
    public function testAfterCheckoutSaveAddressAndClearSession()
    {
        $this->expiredItemHelperMock->expects($this->once())->method('clearExpiredModalCookie')->willReturn(true);

        $this->assertEquals(
            true,
            $this->quoteCreateAfterMock->afterCheckoutSaveAddressAndClearSession($this->subject, true)
        );
    }
}
