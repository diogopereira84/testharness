<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Plugin\Checkout\Block;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Plugin\Checkout\Block\CartItems;
use Magento\Checkout\Block\Cart;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * Test class CartItemsTest
 */
class CartItemsTest extends TestCase
{
    protected $cartItemData;
    protected $quote;
    protected $quoteItem;
    /**
     * @var ExpiredItem $expiredItem
     */
    protected $expiredItem;

    /**
     * @var CheckoutSession $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var CartItems $cartItemsData
     */
    protected $cartItemsData;

    /**
     * @var ConfigProvider $configProvider
     */
    private $configProvider;

    /**
     * @var HttpContext $httpContext
     */
    private $httpContext;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->expiredItem = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExpiredInstanceIds', 'isItemExpiringSoon'])
            ->getMock();
        
        $this->checkoutSession = $this->getMockBuilder(checkoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setExpiredItemIds', 'getExpiredItemIds'])
            ->getMock();
        
        $this->cartItemData = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();
        
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAllItems'])
            ->getMock();
        
        $this->quoteItem = $this->getMockBuilder(QuoteItem::class)
            ->setMethods(['getId'])
            ->disableOriginalconstructor()
            ->getMock();
        
        $this->configProvider = $this->getMockBuilder(ConfigProvider::class)
            ->setMethods(['isEnabled'])
            ->disableOriginalconstructor()
            ->getMock();

        $this->httpContext = $this->getMockBuilder(HttpContext::class)
            ->setMethods(['getValue'])
            ->disableOriginalconstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->cartItemsData = $objectManagerHelper->getObject(
            CartItems::class,
            [
                'expiredItem' => $this->expiredItem,
                'checkoutSession' => $this->checkoutSession,
                'cartItemData' => $this->cartItemData,
                'quote' => $this->quote,
                'quoteItem' => $this->quoteItem,
                'configProvider' => $this->configProvider,
                'httpContext' => $this->httpContext
            ]
        );
    }

    /**
     * Test afterGetItems
     *
     * @return void
     */
    public function testAfterGetItems()
    {
        $arrExpiredItemIds = [0 => '3421', 1 => '3464'];
        $this->configProvider->expects($this->any())->method('isEnabled')->willReturn(1);
        $this->httpContext->expects($this->any())->method('getValue')->willReturn(1);
        $this->expiredItem->expects($this->any())->method('getExpiredInstanceIds')->willReturn($arrExpiredItemIds);
        $this->checkoutSession->expects($this->any())->method('setExpiredItemIds')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('getExpiredItemIds')->willReturn($arrExpiredItemIds);
        $this->cartItemData->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([$this->quoteItem]);

        $this->assertNotEquals($this->cartItemData, $this->cartItemsData->afterGetItems($this->cartItemData));
    }

    /**
     * Test afterGetItems with expired
     *
     * @return void
     */
    public function testAfterGetItemsWithExpired()
    {
        $arrExpiredItemIds = [0 => '3421', 1 => '3464'];
        $this->configProvider->expects($this->any())->method('isEnabled')->willReturn(1);
        $this->httpContext->expects($this->any())->method('getValue')->willReturn(1);
        $this->expiredItem->expects($this->any())->method('getExpiredInstanceIds')->willReturn($arrExpiredItemIds);
        $this->checkoutSession->expects($this->any())->method('setExpiredItemIds')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('getExpiredItemIds')->willReturn($arrExpiredItemIds);
        $this->cartItemData->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([$this->quoteItem]);
        $this->quoteItem->expects($this->any())->method("getId")->willReturn(3421);

        $this->assertNotEquals($this->cartItemData, $this->cartItemsData->afterGetItems($this->cartItemData));
    }

    /**
     * Test afterGetItems with expiry soon
     *
     * @return void
     */
    public function testAfterGetItemsWithExpirySoon()
    {
        $arrExpiredItemIds = [0 => '3421', 1 => '3464'];
        $this->configProvider->expects($this->any())->method('isEnabled')->willReturn(1);
        $this->httpContext->expects($this->any())->method('getValue')->willReturn(1);
        $this->expiredItem->expects($this->any())->method('getExpiredInstanceIds')->willReturn($arrExpiredItemIds);
        $this->checkoutSession->expects($this->any())->method('setExpiredItemIds')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('getExpiredItemIds')->willReturn($arrExpiredItemIds);
        $this->cartItemData->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([$this->quoteItem]);
        $this->expiredItem->expects($this->any())->method("isItemExpiringSoon")->willReturn(true);

        $this->assertNotEquals($this->cartItemData, $this->cartItemsData->afterGetItems($this->cartItemData));
    }

    /**
     * Test afterGetItems with expiry soon
     *
     * @return void
     */
    public function testAfterGetItemsWithoutLogin()
    {
        $arrExpiredItemIds = [0 => '3421', 1 => '3464'];
        $this->configProvider->expects($this->any())->method('isEnabled')->willReturn(0);
        $this->httpContext->expects($this->any())->method('getValue')->willReturn(1);
        $this->expiredItem->expects($this->any())->method('getExpiredInstanceIds')->willReturn($arrExpiredItemIds);
        $this->checkoutSession->expects($this->any())->method('setExpiredItemIds')->willReturn(1);
        $this->checkoutSession->expects($this->any())->method('getExpiredItemIds')->willReturn($arrExpiredItemIds);
        $this->cartItemData->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method("getAllItems")->willReturn([$this->quoteItem]);
        $this->expiredItem->expects($this->any())->method("isItemExpiringSoon")->willReturn(true);

        $this->assertNotEquals($this->cartItemData, $this->cartItemsData->afterGetItems($this->cartItemData));
    }
}
