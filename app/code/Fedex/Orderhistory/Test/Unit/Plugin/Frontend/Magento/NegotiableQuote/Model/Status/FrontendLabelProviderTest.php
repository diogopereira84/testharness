<?php
/**
 * Copyright © Fedex All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Orderhistory\Test\Unit\Plugin\Frontend\Magento\NegotiableQuote\Model\Status;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Orderhistory\Plugin\Frontend\Magento\NegotiableQuote\Model\Status\FrontendLabelProvider;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\Orderhistory\Helper\Data;
use Magento\NegotiableQuote\Model\Status\FrontendLabelProvider as CoreLabel;

class FrontendLabelProviderTest extends \PHPUnit\Framework\TestCase
{
   protected $coreLabel;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $frontendLabel;
    /**
    * @var \Fedex\Orderhistory\Helper\Data $helper
    */
    protected $helper;

    /**
     * Is called before running a test
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
                            ->setMethods(['isModuleEnabled'])
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->coreLabel = $this->getMockBuilder(CoreLabel::class)
                            ->disableOriginalConstructor()
                            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->frontendLabel = $this->objectManager->getObject(
            FrontendLabelProvider::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetStatusLabels()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(true);
        $exectedResult = $this->frontendLabel->afterGetStatusLabels($this->coreLabel,[]);
        $this->assertIsArray($exectedResult);
    }

    /**
     *  test case for afterGetFormattedAddress
     */
    public function testAfterGetStatusLabelsElse()
    {
        $this->helper->expects($this->any())->method('isModuleEnabled')->willReturn(false);
        $exectedResult = $this->frontendLabel->afterGetStatusLabels($this->coreLabel,[]);
        $this->assertIsArray($exectedResult);
    }
}
