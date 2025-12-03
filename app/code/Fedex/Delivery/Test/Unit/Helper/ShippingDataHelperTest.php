<?php
/**
 * Copyright © Fedex Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Delivery\Test\Unit\Helper;

use Fedex\Delivery\Helper\ShippingDataHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ShippingDataHelperTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var ShippingDataHelper $objShippingDataHelper
     */
    protected $objShippingDataHelper;

    /**
     * Prepare test objects.
     */
    protected function setUp(): void
    {
        $this->scopeConfig = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeManager = $this
            ->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->objShippingDataHelper = $this->objectManager->getObject(
            ShippingDataHelper::class,
            [
                'scopeConfig' => $this->scopeConfig,
                'storeManager' => $this->storeManager
            ]
        );
    }
    /**
     * Test getRetailOnePShippingMethods
     * 
     * @return void
     */
    public function testGetRetailOnePShippingMethods()
    {
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getStoreId')->willReturn(23);
        $this->scopeConfig->expects($this->once())->method('getValue')->willReturn('GROUND_US,FEDEX_HOME_DELIVERY');

        $this->assertIsArray($this->objShippingDataHelper->getRetailOnePShippingMethods());
    }
}
