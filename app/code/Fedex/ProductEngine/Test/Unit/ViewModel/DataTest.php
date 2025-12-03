<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
namespace Fedex\ProductEngine\ViewModel;

use Fedex\ProductEngine\ViewModel\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * DataTest for unit test case
 */
class DataTest extends TestCase
{
    protected $sdeHelperMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataHelper;
    const XPATH_PRODUCT_ENGINE_URL = 'product_engine/general/url';
    const GET_IS_SDE_STORE = 'getIsSdeStore';

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var SdeHelper $sdeHelper
     */
    protected $sdeHelper;

    /**
     * @var Store|MockObject
     */
    protected $storeMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this
            ->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMockForAbstractClass();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        
        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([self::GET_IS_SDE_STORE])
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->dataHelper = $this->objectManager->getObject(Data::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'storeManager' => $this->storeManagerMock,
                'sdeHelper' => $this->sdeHelperMock
            ]
        );
    }

    /**
     * Get product engine url
     * @return string
     */
    public function testGetProductEngineUrl()
    {
        $currentStoreId = 1;
        $productEngineUrl = 'https://wwwtest.fedex.com/templates/components/apps/easyprint/content/staticProducts';
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn($currentStoreId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->scopeConfigMock->expects($this->any())->method('getValue')->with(self::XPATH_PRODUCT_ENGINE_URL)->willReturn($productEngineUrl);
        $this->assertEquals($productEngineUrl, $this->dataHelper->getProductEngineUrl());
    }

    /**
     * Get sde store enabled
     * @return boolean
     */
    public function testIsSdeStoreWithTrue()
    {
        $this->sdeHelperMock->expects($this->any())->method(self::GET_IS_SDE_STORE)->willReturn(true);
        $this->assertEquals(true, $this->dataHelper->isSdeStore());
    }

    /**
     * Get sde store not enabled
     * @return boolean
     */
    public function testIsSdeStoreWithFalse()
    {
        $this->sdeHelperMock->expects($this->any())->method(self::GET_IS_SDE_STORE)->willReturn(false);
        $this->assertEquals(false, $this->dataHelper->isSdeStore());
    }
}
