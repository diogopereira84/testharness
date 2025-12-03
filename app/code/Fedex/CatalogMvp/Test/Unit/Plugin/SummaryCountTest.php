<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Session;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Quote\Model\Quote;
use Fedex\CatalogMvp\Plugin\SummaryCount;
use Magento\Checkout\CustomerData\Cart;
use Magento\Checkout\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class SummaryCountTest extends TestCase
{
    protected $catalogMvpHelper;
    protected $checkoutSession;
    protected $quote;
    protected $subject;
    /**
     * @var (\Magento\Checkout\Helper\Data & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $helperData;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $summaryCount;
    protected function setUp(): void
    {
        $this->catalogMvpHelper = $this->createMock(CatalogMvp::class);
        $this->checkoutSession = $this->createMock(Session::class);
        $this->quote = $this->createMock(Quote::class);
        $this->subject = $this->createMock(Cart::class);
        $this->helperData = $this->createMock(Data::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);

        $this->summaryCount = new SummaryCount(
            $this->checkoutSession,
            $this->catalogMvpHelper,
            $this->helperData,
            $this->toggleConfig
        );
    }

    public function testAterGetSectionData()
    {
        // Mocking the required methods for the test scenario
        $this->catalogMvpHelper->expects($this->any())->method('isMvpSharedCatalogEnable')->willReturn(true);
        $this->checkoutSession->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->once())->method('getItemsCount')->willReturn('3');

        // Executing the plugin method
        $result = $this->summaryCount->afterGetSectionData($this->subject, ['summary_count'=>'2']);

        // Asserting that the plugin returns false
        $this->assertIsArray($result);
    }
}
