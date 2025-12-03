<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Plugin\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Plugin\Model\Session as ExpiredItemSession;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Cart\Helper\Data;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;

/**
 * Test class SessionTest
 */
class SessionTest extends TestCase
{
    protected $checkoutSessionMock;
    protected $quote;
    protected $quoteItem;
    protected $toggleConfig;
    protected $cartDataHelperMock;
    /**
     * @var ConfigProvider $configProvider
     */
    private $configProvider;

    /**
     * @var ExpiredItem $expiredItemHelper
     */
    private $expiredItemHelper;

    /**
     * @var ExpiredItemSession $sessionData
     */
    private $sessionData;

    /**
     * @var FuseBidViewModel $fuseBidViewModel
     */
    protected $fuseBidViewModel;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['isEnabled'])
            ->getMock();

        $this->expiredItemHelper = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['callRateApiGetExpiredInstanceIds', 'setExpiredItemMessageCustomerSession'])
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();
        
        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalconstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->cartDataHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['applyFedxExAccountInCheckout'])
            ->getMock();
        
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['isFuseBidToggleEnabled', 'deactivateQuote'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->sessionData = $objectManagerHelper->getObject(
            ExpiredItemSession::class,
            [
                'configProvider' => $this->configProvider,
                'expiredItemHelper' => $this->expiredItemHelper,
                'checkoutSessionMock' => $this->checkoutSessionMock,
                'quote' => $this->quote,
                'quoteItem' => $this->quoteItem,
                'cartDataHelper' => $this->cartDataHelperMock,
                'toggleConfig' => $this->toggleConfig,
                'fuseBidViewModel' => $this->fuseBidViewModel
            ]
        );
    }

    /**
     * Test afterLoadCustomerQuote
     *
     * @return void
     */
    public function testAfterLoadCustomerQuote()
    {
        $this->fuseBidViewModel->expects($this->once())->method('isFuseBidToggleEnabled')->willReturn(true);
        $this->configProvider->expects($this->any())->method('isEnabled')->willReturn(1);
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
            ->willReturn(true);
        $this->cartDataHelperMock->expects($this->any())->method('applyFedxExAccountInCheckout')
            ->with($this->quote)->willReturn(true);
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([$this->quoteItem]);
        $this->assertEquals(
            $this->checkoutSessionMock,
            $this->sessionData->afterLoadCustomerQuote($this->checkoutSessionMock, $this->checkoutSessionMock)
        );
    }
}
