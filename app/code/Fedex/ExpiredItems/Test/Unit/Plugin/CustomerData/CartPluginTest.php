<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\ExpiredItems\Test\Unit\Plugin\CustomerData;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\ExpiredItems\Model\ConfigProvider;
use Fedex\ExpiredItems\Plugin\CustomerData\CartPlugin;
use Magento\Framework\App\Http\Context;
use Magento\Checkout\CustomerData\Cart;
use Magento\Customer\Model\Context as AuthContext;
use Fedex\ExpiredItems\Helper\ExpiredItem;
use Fedex\Base\Helper\Auth as AuthHelper;

/**
 * Test class CartPluginTest
 */
class CartPluginTest extends TestCase
{
    /**
     * @var CartPlugin $cartPluginMock
     */
    protected $cartPluginMock;
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
     * @var AuthHelper $authHelperMock
     */
    private $authHelperMock;

    /**
     * @var Cart $customerCartDataMock
     */
    private $customerCartDataMock;

    /**
     * Set up method
     */
    protected function setUp(): void
    {
        $this->authHelperMock = $this->getMockBuilder(AuthHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['toggleEnabled', 'isLoggedIn'])
            ->getMock();

        $this->configProviderMock = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMiniCartExpiryMessage', 'getMiniCartExpiredMessage'])
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();
       
        $this->expiredItemMock = $this->getMockBuilder(ExpiredItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExpiredInstanceIds', 'isItemExpiringSoon'])
            ->getMock();

        $this->customerCartDataMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->cartPluginMock = $objectManagerHelper->getObject(
            CartPlugin::class,
            [
                'authHelper' =>  $this->authHelperMock,
                'configProvider' => $this->configProviderMock,
                'httpContext' => $this->httpContextMock,
                'expiredItem' => $this->expiredItemMock
            ]
        );
    }

    /**
     * Test method for section data
     *
     * @return void
     */
    public function testAfterGetSectionData()
    {
        $result = [
            'items' => [
                [
                    'instance_id' => 0,
                    'item_id' => 1,
                    'is_expired' => 1
                ],
                [
                    'instance_id' => 22,
                    'item_id' => 2,
                    'is_expiry' => 1
                ]
            ],
            'is_expiry' => 1,
            'is_expired' => 1,
            'expiry_msg' => "",
            'expired_msg' => "",
            'product_engine_expired' => ""
        ];

        $this->httpContextMock->expects($this->any())->method('getValue')->willReturn(AuthContext::CONTEXT_AUTH);
        $this->expiredItemMock->expects($this->any())->method('getExpiredInstanceIds')->willReturn([1, 3]);
        $this->configProviderMock->expects($this->any())
        ->method('getMiniCartExpiredMessage')->willReturn($result['expired_msg']);
        $this->expiredItemMock->expects($this->any())->method('isItemExpiringSoon')->with(2)->willReturn(true);
        $this->configProviderMock->expects($this->any())
        ->method('getMiniCartExpiryMessage')->willReturn($result['expiry_msg']);
        $this->assertEquals($result, $this->cartPluginMock->afterGetSectionData($this->customerCartDataMock, $result));
    }

    /**
     * Test method for section data
     *
     * @return void
     */
    public function testAfterGetSectionDataAuth()
    {
        $result = [
            'items' => [
                [
                    'instance_id' => 0,
                    'item_id' => 1,
                    'is_expired' => 1
                ],
                [
                    'instance_id' => 22,
                    'item_id' => 2,
                    'is_expiry' => 1
                ]
            ],
            'is_expiry' => 1,
            'is_expired' => 1,
            'expiry_msg' => "",
            'expired_msg' => "",
            'product_engine_expired' => ""
        ];
        $this->authHelperMock->expects($this->any())->method('toggleEnabled')->willReturn(true);
        $this->authHelperMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->expiredItemMock->expects($this->any())->method('getExpiredInstanceIds')->willReturn([1, 3]);
        $this->configProviderMock->expects($this->any())
        ->method('getMiniCartExpiredMessage')->willReturn($result['expired_msg']);
        $this->expiredItemMock->expects($this->any())->method('isItemExpiringSoon')->with(2)->willReturn(true);
        $this->configProviderMock->expects($this->any())
        ->method('getMiniCartExpiryMessage')->willReturn($result['expiry_msg']);
        
        $this->assertEquals($result, $this->cartPluginMock->afterGetSectionData($this->customerCartDataMock, $result));
        $this->assertIsArray($this->cartPluginMock->afterGetSectionData($this->customerCartDataMock, $result));
    }
}
