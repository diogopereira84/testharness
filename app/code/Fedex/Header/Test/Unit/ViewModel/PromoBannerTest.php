<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
namespace Fedex\Header\Test\Unit\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Fedex\Header\ViewModel\PromoBanner;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * PromoBannerTest for unit test case
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class PromoBannerTest extends TestCase
{
    protected $promoBannerMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $promoBannerObject;
    protected const PROMO_BANNER_URL = 'header_promo_banner/promobanner_group/promo_banner_url';
    protected const PROMO_BANNER_IS_NEW_TAB = 'header_promo_banner/promobanner_group/promo_banner_is_new_tab';

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerInterfaceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigInterfaceMock;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;
    
    /**
     * Test setUp method
     */
    protected function setUp(): void
    {
        $this->promoBannerMock = $this->createMock(PromoBanner::class);
        $this->scopeConfigInterfaceMock = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();
        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $this->objectManager = new ObjectManager($this);
        $this->promoBannerObject = $this->objectManager->getObject(
            PromoBanner::class,
            [
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'storeManagerInterface' => $this->storeManagerInterfaceMock
            ]
        );
    }

    /**
     * Test method for getCurrentStoreId
     *
     * @return void
     */
    public function testGetCurrentStoreId()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->assertEquals($storeId, $this->promoBannerObject->getCurrentStoreId());
    }

    /**
     * Test method for gePromoBannerConfig
     *
     * @return void
     */
    public function testGePromoBannerConfigg()
    {
        $storeId = 1;
        $this->scopeConfigInterfaceMock->expects($this->any())
        ->method('getValue')->with(self::PROMO_BANNER_URL)->willReturn(1);

        $this->assertEquals(1, $this->promoBannerObject->gePromoBannerConfig(self::PROMO_BANNER_URL, $storeId));
    }

    /**
     * Test method for getPromoBannerUrl
     *
     * @return void
     */
    public function testGetPromoBannerUrl()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerInterfaceMock->expects($this->any())
        ->method('getStore')->willReturn($this->storeMock);
        $this->promoBannerMock->expects($this->any())
        ->method('gePromoBannerConfig')->with(self::PROMO_BANNER_URL, $storeId)->willReturn(null);

        $this->assertEquals(null, $this->promoBannerObject->getPromoBannerUrl());
    }

    /**
     * Test method for getPromoBannerIsNewTab
     *
     * @return void
     */
    public function testGetPromoBannerIsNewTab()
    {
        $storeId = 1;
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($storeId);
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->promoBannerMock->expects($this->any())
        ->method('gePromoBannerConfig')->with(self::PROMO_BANNER_IS_NEW_TAB, $storeId)->willReturn(null);
        
        $this->assertEquals(null, $this->promoBannerObject->getPromoBannerIsNewTab());
    }

    /**
     * Test method for dataProviderEnabledDisabled
     *
     * @return array
     */
    public function dataProviderEnabledDisabled(): array
    {
        return [[0],[0]];
    }
}
