<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Test\Unit\Helper;

use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class FuseBidHelperTest extends TestCase
{
    /**
     * @var FuseBidHelper|MockObject
     */
    private $fuseBidHelper;

    /**
     * @var context|MockObject
     */
    private $contextMock;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var ToggleConfig|MockObject
     */
    private $toggleConfigMock;

    /**
     * @var CartFactory $cartFactory
     */
    protected $cartFactory;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * Setup function
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->cartFactory = $this->getMockBuilder(CartFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getQuote', 'getIsBid', 'getId', 'setIsActive', 'save'])
            ->getMock();

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save', 'getId'])
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['replaceQuote'])
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['info'])
            ->getMockForAbstractClass();

        $this->fuseBidHelper = new FuseBidHelper(
            $this->contextMock,
            $this->scopeConfig,
            $this->toggleConfigMock,
            $this->cartFactory,
            $this->quoteFactory,
            $this->checkoutSession,
            $this->logger
        );
    }

    /**
     * Test function for validateToggleConfig
     *
     * @return void
     */
    public function testValidateToggleConfig()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isFuseBidGloballyEnabled());
    }

    /**
     * Test deactivateQuote
     *
     * @return void
     */
    public function testDeactivateQuote()
    {
        $this->cartFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->cartFactory->expects($this->once())->method('getQuote')->willReturnSelf();
        $this->cartFactory->expects($this->any())->method('getIsBid')->willReturn(1);
        $this->cartFactory->expects($this->once())->method('getId')->willReturn(1);
        $this->cartFactory->expects($this->once())->method('setIsActive')->willReturnSelf();
        $this->cartFactory->expects($this->once())->method('save')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('save')->willReturnSelf();
        $this->quoteFactory->expects($this->once())->method('getId')->willReturn(35434);
        $this->checkoutSession->expects($this->once())->method('replaceQuote')->willReturnSelf();

        $this->assertNULL($this->fuseBidHelper->deactivateQuote());
    }

    /**
     * Test isRateQuoteDetailApiEnabed
     *
     * @return void
     */
    public function testIsRateQuoteDetailApiEnabed()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isRateQuoteDetailApiEnabed());
    }

    /**
     * Test isSendRetailLocationIdEnabled
     *
     * @return void
     */
    public function testIsSendRetailLocationIdEnabled()
    {
        $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isSendRetailLocationIdEnabled());
    }

    /**
     * Test isBidCheckoutEnabled
     *
     * @return void
     */
    public function testIsBidCheckoutEnabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isBidCheckoutEnabled());
    }

    /**
     * Test isToggleTeamMemberInfoEnabled
     *
     * @return void
     */
    public function testIsToggleTeamMemberInfoEnabled()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isToggleTeamMemberInfoEnabled());
    }
    /**
     * Test isBidCheckoutEnabled
     *
     * @return void
     */
    public function testIsToggleD215974Enabled()
    {
        $this->toggleConfigMock->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('tiger_d215974')
            ->willReturn(true);

        $this->assertTrue($this->fuseBidHelper->isToggleD215974Enabled());
    }
}
