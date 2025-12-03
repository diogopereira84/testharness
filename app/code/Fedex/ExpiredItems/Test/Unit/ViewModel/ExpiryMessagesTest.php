<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\ViewModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Magento\Framework\App\Http\Context;
use Fedex\ExpiredItems\ViewModel\ExpiryMessages;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Context as AuthContext;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\App\Request\Http;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;

/**
 * Test class ExpiryMessagesTest
 */
class ExpiryMessagesTest extends TestCase
{
    protected $quote;
    protected $expiryMessagesMock;
    /**
     * @var ConfigProvider $configProviderMock
     */
    private $configProviderMock;

    /**
     * @var Context $httpContextMock
     */
    private $httpContextMock;

    /**
     * @var ExpiredItem $expiredItemMock
     */
    private $expiredItemMock;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

     /**
      * @var Item $itemMock
      */
    private $itemMock;

    /**
     * @var Http $request
     */
    private $requestMock;

    /**
     * @var CheckoutSession $checkoutSessionMock
     */
    private $checkoutSessionMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->configProviderMock = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->expiredItemMock = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getExpiredInstanceIds', 'isItemExpiringSoon', 'isItemExpired','isBundleItemExpiringSoon'])
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getCode'])
            ->getMockForAbstractClass();

        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getItemId','getChildren','getProductType'])
            ->getMock();

         $this->requestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['getQuote'])
            ->getMock();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getAllVisibleItems'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->expiryMessagesMock = $objectManagerHelper->getObject(
            ExpiryMessages::class,
            [
                'configProvider' => $this->configProviderMock,
                'httpContext' => $this->httpContextMock,
                'expiredItem' => $this->expiredItemMock,
                'storeManager' => $this->storeManager,
                'request' => $this->requestMock,
                'checkoutSession' => $this->checkoutSessionMock
            ]
        );
        $this->expiryMessagesMock->expiredItems = [12, 123, 111];
    }

    /**
     * Test method for get config
     *
     * @return void
     */
    public function testGetConfig()
    {
        $this->assertIsObject($this->expiryMessagesMock->getConfig());
    }

    /**
     * Test method for isCustomerLoggedIn
     *
     * @return void
     */
    public function testIsCustomerLoggedIn()
    {
        $expectedResult = true;

        $this->assertEquals($expectedResult, $this->expiryMessagesMock->isCustomerLoggedIn());
    }

    /**
     * Test method for isCustomerLoggedIn without cookie
     *
     * @return void
     */
    public function testIsCustomerLoggedInWithoutCookie()
    {
        $expectedResult = true;

        $this->assertEquals($expectedResult, $this->expiryMessagesMock->isCustomerLoggedIn());
    }

    /**
     * Test method for get expired items
     *
     * @return void
     */
    public function testGetExpiredItems()
    {
        $expectedResult = [];
        $this->expiryMessagesMock->expiredItems = [];
        $this->expiredItemMock
            ->expects($this->any())
            ->method('getExpiredInstanceIds')
            ->willReturn([]);

        $this->assertEquals($expectedResult, $this->expiryMessagesMock->getExpiredItems());
    }

    /**
     * Test method for is item expired with no id match
     *
     * @return void
     */
    public function testIsItemExpired()
    {
        $this->itemMock->expects($this->any())->method('getId')->willReturn(54274);
        $this->expiredItemMock
            ->expects($this->any())
            ->method('isItemExpired')
            ->willReturn(false);

        $this->assertEquals(false, $this->expiryMessagesMock->isItemExpired($this->itemMock));
    }

    /**
     * Test method for is item expired with Id match
     *
     * @return void
     */
    public function testIsItemExpiredWithIdMatch()
    {
        $this->itemMock->expects($this->any())->method('getId')->willReturn(111);

        $this->assertEquals(true, $this->expiryMessagesMock->isItemExpired($this->itemMock));
    }

    /**
     * Test method for isCanvaPage
     *
     * @return boolean
     */
    public function testIsCanvaPage()
    {
        $expectedResult = true;
        $this->requestMock->expects($this->once())->method('getFullActionName')->willReturn(true);

        $this->assertEquals($expectedResult, $this->expiryMessagesMock->isCanvaPage());
    }

    /**
     * Test method for isCanvaPage
     *
     * @return boolean
     */
    public function testIsCanvaPageForFalse()
    {
        $expectedResult = false;
        $this->requestMock->expects($this->once())->method('getFullActionName')->willReturn(false);

        $this->assertEquals($expectedResult, $this->expiryMessagesMock->isCanvaPage());
    }

    /**
     * Test method for isItemExpiringSoon
     *
     * @return void
     */
    public function testIsItemExpiringSoon()
    {
        $this->expiredItemMock
            ->expects($this->any())
            ->method('isItemExpiringSoon')
            ->with(123)
            ->willReturn(false);

        $this->assertEquals(false, $this->expiryMessagesMock->isItemExpiringSoon(123));
    }

    /**
     * Test method for isAnyItemExpiringSoon
     *
     * @return void
     */
    public function testIsAnyItemExpiringSoon()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->itemMock->method('getItemId')->willReturnOnConsecutiveCalls(123, 1234);
        $this->itemMock->method('getChildren')->willReturnOnConsecutiveCalls(null);
        $this->itemMock->method('getProductType')->willReturnOnConsecutiveCalls('simple');
        $this->expiredItemMock->expects($this->any())->method('isItemExpiringSoon')->with(123)->willReturn(true);

        $this->assertEquals(true, $this->expiryMessagesMock->isAnyItemExpiringSoon());
    }

    /**
     * Test method for isAnyItemExpiringSoon
     *
     * @return void
     */
    public function testIsAnyItemExpiringSoonProductBundle()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([0 => $this->itemMock]);
        $this->itemMock->method('getItemId')->willReturnOnConsecutiveCalls(123, 1234);
        $childQuoteItem = $this->createMock(Item::class);
        $this->itemMock->method('getChildren')->willReturnOnConsecutiveCalls([$childQuoteItem]);
        $this->itemMock->method('getProductType')->willReturnOnConsecutiveCalls('bundle');
        $this->expiredItemMock->expects($this->any())->method('isItemExpiringSoon')->with(123)->willReturn(true);
        $this->expiredItemMock->expects($this->any())
            ->method('isBundleItemExpiringSoon')
            ->with($this->itemMock)
            ->willReturn(true);

        $this->assertEquals(true, $this->expiryMessagesMock->isAnyItemExpiringSoon());
    }

    /**
     * Test method for isAnyItemExpiringSoon Without Items
     *
     * @return void
     */
    public function testIsAnyItemExpiringSoonWithoutItems()
    {
        $this->checkoutSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->any())->method('getAllVisibleItems')->willReturn([]);

        $this->assertEquals(false, $this->expiryMessagesMock->isAnyItemExpiringSoon());
    }
}
