<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\CustomerData;

use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\CustomerData\MiniCartItem;
use Magento\Checkout\Model\Session;
use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\CustomerData\ItemPoolInterface;
use Magento\Framework\View\LayoutInterface;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Magento\Framework\App\Http\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * Test class MiniCartItemTest
 */
class MiniCartItemTest extends TestCase
{
    /**
     * @var MiniCartItem $miniCartItem
     */
    private $miniCartItem;

    /**
     * @var ExpiredItem $expiredItemMock
     */
    private $expiredItemMock;

    /**
     * @var Context $httpContextMock
     */
    private $httpContextMock;

    /**
     * @var CustomerSession $customerSessionMock
     */
    private $customerSessionMock;

    /**
     * @var AuthHelper $authHelperMock
     */
    private $authHelperMock;

    /**
     * @var Session $checkoutSessionMock
     */
    private $checkoutSessionMock;

    /**
     * @var Url $catalogUrlMock
     */
    private $catalogUrlMock;

    /**
     * @var Cart $checkoutCartMock
     */
    private $checkoutCartMock;

    /**
     * @var Data $checkoutHelperMock
     */
    private $checkoutHelperMock;

    /**
     * @var ItemPoolInterface $itemPoolInterfaceMock
     */
    private $itemPoolInterfaceMock;

    /**
     * @var LayoutInterface $layoutMock
     */
    private $layoutMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->expiredItemMock = $this->createMock(ExpiredItem::class);
        $this->httpContextMock = $this->createMock(Context::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->authHelperMock = $this->createMock(AuthHelper::class);
        $this->checkoutSessionMock = $this->createMock(Session::class);
        $this->catalogUrlMock = $this->createMock(Url::class);
        $this->checkoutCartMock = $this->createMock(Cart::class);
        $this->checkoutHelperMock = $this->createMock(Data::class);
        $this->itemPoolInterfaceMock = $this->createMock(ItemPoolInterface::class);
        $this->layoutMock = $this->createMock(LayoutInterface::class);

        $this->miniCartItem = new MiniCartItem(
            $this->checkoutSessionMock,
            $this->catalogUrlMock,
            $this->checkoutCartMock,
            $this->checkoutHelperMock,
            $this->itemPoolInterfaceMock,
            $this->layoutMock,
            $this->expiredItemMock,
            $this->httpContextMock,
            $this->customerSessionMock,
            $this->authHelperMock
        );
    }

    /**
     * Test getRecentItems
     *
     * @return void
     */
    public function testGetRecentItemsWithExpiredItems()
    {
        $item1 = ['item_id' => 1];
        $item2 = ['item_id' => 2];
        $item3 = ['item_id' => 3];
        $items = [$item1, $item2, $item3];

        $this->expiredItemMock->expects($this->once())
            ->method('getExpiredInstanceIds')->willReturn($items);

        $this->expiredItemMock->expects($this->any())->method('isItemExpiringSoon')->willReturn(true);

        $this->authHelperMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->checkoutCartMock->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();

        $this->checkoutCartMock->expects($this->any())
            ->method('getItems')
            ->willReturn($items);

        $result = $this->miniCartItem->getRecentItems();
        $expectedResult = array_merge([$item1], [$item2], [$item3]);

        $this->assertNotEquals($expectedResult, $result);
    }
}
