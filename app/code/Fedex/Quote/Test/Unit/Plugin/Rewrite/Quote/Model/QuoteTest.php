<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Quote\Test\Unit\Plugin\Rewrite\Quote\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Quote\Plugin\Rewrite\Quote\Model\Quote;

/**
 * Test class for Quote functions
 */
class QuoteTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteData;
    /**
     * @var CustomerSession $customerSession
     */
    protected $customerSession;

    /**
     * setUp function for all contructor params
     */
    public function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods([
                'setCompareItem',
                'unsCompareItem'
            ])->disableOriginalConstructor()->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->quoteData = $this->objectManager->getObject(
            Quote::class,
            [
                'customerSession' => $this->customerSession
            ]
        );
    }

    /**
     * Test beforeMerge
     *
     * @return void
     */
    public function testBeforeMerge()
    {
        $returnValue = true;
        $this->customerSession->expects($this->once())->method('setCompareItem')->willReturn($returnValue);

        $this->assertNUll($this->quoteData->beforeMerge('test', true));
    }

    /**
     * Test afterMerge
     *
     * @return void
     */
    public function testAfterMerge()
    {
        $returnValue = true;
        $this->customerSession->expects($this->once())->method('unsCompareItem')->willReturn($returnValue);

        $this->assertTrue($this->quoteData->afterMerge('test', true));
    }
}
