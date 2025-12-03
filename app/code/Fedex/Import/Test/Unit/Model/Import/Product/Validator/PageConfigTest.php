<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Test\Unit\Model\Import\Product\Validator;

use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Import\Model\Import\Product\Validator\PageConfig;
use Magento\Store\Api\Data\StoreInterface;

class PageConfigTest extends TestCase
{
    protected $storeManagerInterfaceMock;
    protected $storeInterfaceMock;
    protected $Mock;
    protected function setUp(): void
    {
        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
        ->disableoriginalConstructor()
        ->getMock();
        $this->storeInterfaceMock = $this->getMockBuilder(StoreInterface::class)
        ->setMethods([
            'getId',
            'setId',
            'getCode',
            'setCode',
            'getName',
            'setName',
            'getWebsiteId',
            'setWebsiteId',
            'getStoreGroupId',
            'setIsActive',
            'getIsActive',
            'setStoreGroupId',
            'getExtensionAttributes',
            'setExtensionAttributes'
        ])
        ->disableoriginalConstructor()
        ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->Mock = $objectManagerHelper->getObject(
            PageConfig::class,
            [
                'storeManager' => $this->storeManagerInterfaceMock
            ]
        );
    }
    
    /**
     * Test Case for getDefaultStoreId
     *
     * @return void
     */
    public function testgetDefaultStoreId()
    {
        $this->storeManagerInterfaceMock->expects($this->any())
        ->method('getDefaultStoreView')->willReturn($this->storeInterfaceMock);
        $this->storeInterfaceMock->expects($this->any())->method('getId')->willReturn(2);
        $this->Mock->getDefaultStoreId();
    }

    /**
     * Test Case for validate Store Id
     *
     * @return void
     */
    public function testvalidateStoreId()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStores')->willReturn([0,1,2]);
        $this->Mock->validateStoreId(12);
    }

    /**
     * Test Case for validate Store Id with string
     *
     * @return void
     */
    public function testvalidateStoreIdWithString()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStores')->willReturn([]);
        $this->Mock->validateStoreId('ABC');
    }

    /**
     * Test Case for validate Store Id for true condition
     *
     * @return void
     */
    public function testvalidateStoreIdWithoutput()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStores')->willReturn([0,1,2,3]);
        $this->Mock->validateStoreId(2);
    }

    /**
     * Test Case for validateMode
     *
     * @return void
     */
    public function testvalidateMode()
    {
        $this->Mock->validateMode('ABC');
    }

    /**
     * Test Case for validateMode True
     *
     * @return void
     */
    public function testvalidateModeTrue()
    {
        $this->Mock->validateMode('PRODUCTS');
    }
}
