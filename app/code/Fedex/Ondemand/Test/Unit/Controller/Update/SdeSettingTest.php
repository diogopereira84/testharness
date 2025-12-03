<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Ondemand\Test\Unit\Controller\Update;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\StoreFactory;
use Psr\Log\LoggerInterface;
use \Magento\Cms\Model\Page;
use \Magento\Cms\Model\PageFactory;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;
use \Magento\Framework\App\Cache\TypeListInterface;
use \Magento\Framework\App\Cache\Frontend\Pool;
use \Magento\Config\Model\ResourceModel\Config;
use \Magento\Cms\Api\BlockRepositoryInterface;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Cms\Model\BlockFactory;
use \Magento\Cms\Model\Block;
use Fedex\Ondemand\Controller\Update\SdeSetting;
use \Magento\Store\Model\GroupFactory;
use \Magento\Store\Model\Group;
use Magento\Framework\Phrase;
use \Magento\Framework\App\ResourceConnection;

class SdeSettingTest extends TestCase
{
    protected $PageMock;
    /**
     * @var (\Magento\Cms\Model\PageFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $PageFactoryMock;
    /**
     * @var (\Magento\Store\Model\StoreFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeFactoryMock;
    /**
     * @var (\Magento\Framework\App\Config\ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $scopeConfigInterfaceMock;
    /**
     * @var (\Magento\Store\Model\ScopeInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $ScopeInterfaceMock;
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\App\Cache\TypeListInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $typeListInterfaceMock;
    /**
     * @var (\Magento\Framework\App\Cache\Frontend\Pool & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $poolMock;
    protected $ConfigMock;
    /**
     * @var (\Magento\Cms\Api\BlockRepositoryInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $blockRepositoryInterfaceMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaBuilder & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaBuilderMock;
    protected $blockFactoryMock;
    protected $GroupFactoryMock;
    protected $groupMock;
    /**
     * @var (\Magento\Cms\Model\Block & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $block;
    protected $resourceConnectionMock;
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;
    protected $sdeSettigMock;
    /**
     * Init mocks for tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->PageMock  = $this->getMockBuilder(Page::class)
            ->setMethods(['load', 'getIdentifier', 'getTitle', 'getContentHeading', 'getContent', 'getPageLayout'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->PageFactoryMock  = $this->getMockBuilder(PageFactory::class)
            ->setMethods(['load', 'create', 'getGroupId', 'getStoreIds', 'setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeFactoryMock  = $this->getMockBuilder(StoreFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigInterfaceMock  = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->ScopeInterfaceMock  = $this->getMockBuilder(ScopeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(['info', 'error'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->typeListInterfaceMock  = $this->getMockBuilder(TypeListInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->poolMock  = $this->getMockBuilder(Pool::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ConfigMock  = $this->getMockBuilder(Config::class)
            ->setMethods(['create', 'saveConfig', 'load'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockRepositoryInterfaceMock  = $this->getMockBuilder(BlockRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock  = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->setMethods(['load', 'reindexAll', 'reindexRow'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->blockFactoryMock  = $this->getMockBuilder(BlockFactory::class)
            ->setMethods(['create', 'getCollection', 'addFieldToFilter', 'load', 'getRowId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->GroupFactoryMock = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['load', 'create', 'getStoreIds', 'getGroupId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->groupMock = $this->getMockBuilder(Group::class)
            ->setMethods(['load', 'create', 'getStoreIds', 'getGroupId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = $this->getMockBuilder(Block::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getCollection', 'setContent', 'setStores', 'setTitle',
                'setIsActive', 'save'
            ])
            ->getMock();

        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getTableName', 'getConnection', 'insert'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->sdeSettigMock = $this->objectManagerHelper->getObject(
            SdeSetting::class,
            [
                'page' => $this->PageMock,
                'groupFactory' => $this->GroupFactoryMock,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'cacheTypeList' => $this->typeListInterfaceMock,
                'config' => $this->ConfigMock,
                'blockFactory' => $this->blockFactoryMock,
                'logger' => $this->loggerMock,
                'resourceConnection' => $this->resourceConnectionMock
            ]
        );
    }

    /**
     * testExecute
     */
    public function testExecute()
    {
        $tables = ['customer_entity'];
        $b2bStoreCode = "b2b_store";
        $ondemandStoreCode = "ondemand";

        $groupId = 9;
        $storeIds = [9, 10, 11, 68];


        $this->GroupFactoryMock->expects($this->any())->method('create')->willReturn($this->GroupFactoryMock);
        $this->GroupFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->GroupFactoryMock->expects($this->any())->method('getGroupId')->willReturn($groupId);
        $this->GroupFactoryMock->expects($this->any())->method('getStoreIds')->willReturn($storeIds);
        $this->testUpdateCmsPages();
        $this->testUpdateCmsBlockForOndemand();
        $this->assertNull($this->sdeSettigMock->execute());
    }

    public function testsetConfigData()
    {

        $path = 'sde/sde_mask/sde_mask_img';
        $value = 'mask.png';
        $scopeId = '98';
        $this->ConfigMock->expects($this->any())->method('saveConfig')->willReturn(true);
        $this->assertNotNull($this->sdeSettigMock->setConfigData($path, $value, $scopeId));
    }

    public function testsetConfigDataWithException()
    {

        $path = 'sde/sde_mask/sde_mask_img';
        $value = 'mask.png';
        $scopeId = '98';
        $exception = new \Exception();
        $this->ConfigMock->expects($this->any())->method('saveConfig')->willThrowException($exception);
        $this->assertNull($this->sdeSettigMock->setConfigData($path, $value, $scopeId));
    }


    public function testUpdateCmsPages()
    {
        $pageKey = 'sde_page_footer';
        $storeId = 65;
        $this->PageMock->expects($this->any())->method('load')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getTableName')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('insert')->willReturnSelf();
        $this->assertTrue($this->sdeSettigMock->UpdateCmsPages($pageKey, $storeId));
    }


    public function testUpdateCmsPagesWithException()
    {
        $pageKey = 'sde_page_footer';
        $storeId = 65;
        $exception = new \Exception();
        $this->PageMock->expects($this->any())->method('load')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getTableName')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('insert')->willThrowException($exception);
        $this->assertNull($this->sdeSettigMock->UpdateCmsPages($pageKey, $storeId));
    }
    /**
     * testExecuteWithExecption
     */
    public function testExecuteWithExecption()
    {

        $phrase = new Phrase(__('Exception message'));
        $exception = new \Exception();
        $this->GroupFactoryMock->expects($this->any())->method('create')->willReturn($this->groupMock);
        $this->groupMock->expects($this->any())->method('load')->willThrowException($exception);
        $this->GroupFactoryMock->expects($this->any())->method('create')->willThrowException($exception);
        $this->assertNull($this->sdeSettigMock->execute());
    }

    public function testUpdateCmsBlockForOndemand()
    {
        $sdeStoreIds = '3656';
        $ondemandStoreId = '8965';
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('getTableName')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('getRowId')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('insert')->willReturnSelf();
        $this->assertTrue($this->sdeSettigMock->UpdateCmsBlockForOndemand($sdeStoreIds, $ondemandStoreId));
    }


    public function testUpdateCmsBlockForOndemandWithException()
    {
        $sdeStoreIds = '3656';
        $ondemandStoreId = '8965';
        $exception = new \Exception();
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')->willThrowException($exception);
        $this->resourceConnectionMock->expects($this->any())->method('getTableName')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->blockFactoryMock->expects($this->any())->method('getRowId')->willReturnSelf();
        $this->resourceConnectionMock->expects($this->any())->method('insert')->willReturnSelf();
        $this->assertNull($this->sdeSettigMock->UpdateCmsBlockForOndemand($sdeStoreIds, $ondemandStoreId));
    }
}
