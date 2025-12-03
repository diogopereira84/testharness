<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Model\Permission\Index;
use Fedex\CustomerGroup\Observer\ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver;
use Magento\Customer\Model\Session;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Helper\SelfReg as SelfRegHelper;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver
 */
class ApplyCategoryPermissionOnIsActiveFilterToCollectionObserverTest extends TestCase
{
    protected $selfRegMock;
    /**
     * @var ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver
     */
    protected $observer;

    /**
     * @var ConfigInterface|MockObject
     */
    protected $permissionsConfig;

    /**
     * @var Index|MockObject
     */
    protected $permissionIndex;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManager;

    /**
     * @var Observer|MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->permissionsConfig = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->permissionIndex = $this->createMock(Index::class);

        $this->eventObserverMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selfRegMock = $this->getMockBuilder(SelfRegHelper::class)
            ->setMethods(['isSelfRegCustomerAdmin'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer =  new ApplyCategoryPermissionOnIsActiveFilterToCollectionObserver(
            $this->permissionsConfig,
            $this->storeManager,
            $this->createMock(Session::class),
            $this->permissionIndex,
            $this->selfRegMock
        );
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermissionOnIsActiveFilterToCollection()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->selfRegMock
            ->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['category_collection' => 'Some Category Collection']));

        $this->permissionIndex
            ->expects($this->once())
            ->method('addIndexToCategoryCollection')
            ->with('Some Category Collection', $this->anything(), $this->anything());

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $this->observer->execute($this->eventObserverMock);
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermissionOnIsActiveFilterToCollectionwithFalse()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);

        $this->selfRegMock
            ->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);
            
        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['category_collection' => 'Some Category Collection']));

        $this->permissionIndex
            ->expects($this->any())
            ->method('addIndexToCategoryCollection')
            ->with('Some Category Collection', $this->anything(), $this->anything());

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $this->observer->execute($this->eventObserverMock);
    }
}
