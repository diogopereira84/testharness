<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Block;

/**
 * Quote History Block test class
 */
use Fedex\FuseBiddingQuote\Block\QuoteHistory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit\Framework\TestCase;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Magento\Customer\Block\Account\SortLinkInterface;
use Fedex\SSO\ViewModel\SsoConfiguration;

class QuoteHistoryTest extends TestCase
{

    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $urlInterfaceMock;
    /**
     * @var (\Magento\Framework\Escaper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $escaperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customBlock;
    /**
     * @var FuseBidHelper|MockObject
     */
    protected $fuseBidHelper;

    /**
     * @var SsoConfiguration|MockObject
     */
    protected $ssoConfiguration;

    /**
     * @var $sort_order
     */
    public const SORT_ORDER = 93;

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterfaceMock = $this
            ->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->setMethods(['getUrl','getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->escaperMock = $this
            ->getMockBuilder(\Magento\Framework\Escaper::class)
            ->setMethods(['escapeHtml'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->fuseBidHelper = $this->getMockBuilder(FuseBidHelper::class)
            ->setMethods(['isFuseBidGloballyEnabled'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods(['isRetail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->customBlock = $this->objectManager->getObject(
            QuoteHistory::class,
            [
                'context' => $this->context,
                '_urlBuilder' => $this->urlInterfaceMock,
                '_escaper' => $this->escaperMock,
                'fuseBidHelper' => $this->fuseBidHelper,
                'ssoConfiguration' => $this->ssoConfiguration
            ]
        );
    }

    /**
     * Assert _toHtml.
     *
     * @return string
     */
    public function testToHtml()
    {
        $testMethod = new \ReflectionMethod(
            QuoteHistory::class,
            '_toHtml',
        );
        $this->fuseBidHelper->expects($this->any())->method('isFuseBidGloballyEnabled')->willReturn(true);
        $this->ssoConfiguration->expects($this->any())->method('isRetail')->willReturnSelf();
        $this->urlInterfaceMock->method('getCurrentUrl')
         ->willReturn('https://staging3.office.fedex.com/default/uploadtoquote/index/quotehistory');
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->customBlock);

         $this->assertIsString($expectedResult);
    }

    /**
     * Assert _toHtml in Negative case
     *
     * @return ''
     */
    public function testToHtmlWhenModuleDisable()
    {
        $testMethod = new \ReflectionMethod(
            QuoteHistory::class,
            '_toHtml',
        );
        $this->fuseBidHelper->expects($this->any())->method('isFuseBidGloballyEnabled')->willReturn(true);
        $this->ssoConfiguration->expects($this->any())->method('isRetail')->willReturnSelf();
        $testMethod->setAccessible(true);
        $expectedResult = $testMethod->invoke($this->customBlock);

        $this->assertIsString($expectedResult);
    }

    /**
     * Test Case getSortOrder()
     */
    public function testGetSortOrder()
    {
        $this->customBlock->getSortOrder();
    }
}
