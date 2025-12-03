<?php
/**
 * Copyright © NA All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\NegotiableQuote\Block\Order\Info;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\NegotiableQuote\Block\Order\Info\CreationInfo;
use Fedex\Orderhistory\Helper\Data;
use Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Block\Order\Info\CreationInfo as CreationInfoPlugin;

class CreationInfoTest extends \PHPUnit\Framework\TestCase
{
    protected $creationInfo;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $creationInfoMock;
    /**
     * @var Data
     */
    protected $helper;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->setMethods(['isPrintReceiptRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->creationInfo = $this->getMockBuilder(CreationInfo::class)
            ->setMethods(['setTemplate'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->creationInfoMock = $this->objectManager->getObject(
            CreationInfoPlugin::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * testBeforeToHtml
     */
    public function testBeforeToHtml()
    {
        $this->helper->expects($this->any())->method('isPrintReceiptRetail')->willReturn(true);
        $this->creationInfo->expects($this->any())->method('setTemplate')->willReturnSelf();
        $this->assertEquals(null, $this->creationInfoMock->beforeToHtml($this->creationInfo));
    }
}
