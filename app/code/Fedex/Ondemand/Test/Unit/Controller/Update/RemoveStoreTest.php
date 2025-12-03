<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Update;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\GroupFactory;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\GroupRepository;
use Magento\Store\Model\StoreRepository;
use Magento\Framework\Registry;
use Fedex\Ondemand\Controller\Update\RemoveStore;

class RemoveStoreTest extends TestCase
{
    protected $groupFactory;
    protected $group;
    protected $store;
    protected $registry;
    protected $storeRepository;
    protected $groupRepository;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $removeStore;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->groupFactory  = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->group  = $this->getMockBuilder(Group::class)
            ->setMethods(['load','getId','getStoreIds','delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->store  = $this->getMockBuilder(Store::class)
            ->setMethods(['delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->setMethods(['registry','register'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeRepository = $this->getMockBuilder(StoreRepository::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupRepository = $this->getMockBuilder(GroupRepository::class)
            ->setMethods(['get'])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->objectManagerHelper = new ObjectManager($this);
        
        $this->removeStore = $this->objectManagerHelper->getObject(
            RemoveStore::class,
            [
                    'storeRepository' => $this->storeRepository,
                    'groupRepository' => $this->groupRepository,
                    'groupFactory' => $this->groupFactory,
                    'registry' => $this->registry
                ]
        );
    }
    
    /**
     * testExecute
     */
    public function testExecute()
    {
        $b2bgroupId = 9;
        $sdegroupId = 11;
        $b2bstoreIds = [9, 10, 11, 68];
        $sdestoreIds = [100,102];
        
        $this->registry->expects($this->any())->method('registry')->willReturn(null);
        $this->registry->expects($this->any())->method('register')->willReturnSelf();
        $this->groupFactory->expects($this->any())->method('create')->willReturn($this->group);
        $this->group->expects($this->any())->method('load')->willReturnSelf();
        $this->group
            ->method('getId')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls($b2bgroupId, $sdegroupId);
        $this->group
            ->method('getStoreIds')
            ->withConsecutive([], [])
            ->willReturnOnConsecutiveCalls($b2bstoreIds, $sdestoreIds);
        $this->storeRepository->expects($this->any())->method('get')->willReturn($this->store);
        $this->store->expects($this->any())->method('delete')->willReturnSelf();

        $this->groupRepository->expects($this->any())->method('get')->willReturn($this->group);
        $this->group->expects($this->any())->method('delete')->willReturnSelf();
        $this->assertNull($this->removeStore->execute());
    }
}
