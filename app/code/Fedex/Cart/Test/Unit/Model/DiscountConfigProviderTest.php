<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Cart\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Psr\Log\LoggerInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Quote\Model\Quote;
use Fedex\Cart\Model\DiscountConfigProvider;

class DiscountConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    protected $quote;
    protected $discountConfigProvider;
    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $loggerInterface;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getId', 'collectTotals'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerInterface = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->discountConfigProvider = $this->objectManager->getObject(
            DiscountConfigProvider::class,
            [
                'checkoutSession' => $this->session,
                'logger' => $this->loggerInterface
            ]
        );
    }

    /**
     * Test Method for round.
     */
    public function testgetConfig()
    {
        $this->session->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getId')->willReturnSelf();
        $this->quote->expects($this->any())->method('collectTotals')->willReturnSelf();
        $this->assertNotNull($this->discountConfigProvider->getConfig());
    }
}
