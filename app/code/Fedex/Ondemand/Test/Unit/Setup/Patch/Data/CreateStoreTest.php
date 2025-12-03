<?php
namespace Fedex\Ondemand\Test\Unit\Setup\Patch\Data;

use Fedex\Ondemand\Setup\Patch\Data\CreateStore;
use Magento\Framework\DB\Adapter\Pdo\Mysql\Interceptor;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Group;
use Magento\Store\Model\GroupFactory;
use PHPUnit\Framework\TestCase;

/**
 * @codeCoverageIgnore
 */
class CreateStoreTest extends TestCase
{

    protected $group;
    protected $mysqlInterceptor;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $createStore;
    /** @var GroupFactory */
    private $groupFactory;

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->groupFactory = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->group = $this->getMockBuilder(GroupFactory::class)
            ->setMethods(['load', 'getWebsiteId', 'getRootCategoryId', 'getDefaultStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->mysqlInterceptor = $this->getMockBuilder(Interceptor::class)
            ->setMethods(['insertArray', 'lastInsertId', 'update'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->createStore = $this->objectManager->getObject(
            CreateStore::class,
            [
                'groupFactory' => $this->groupFactory,
                'moduleDataSetup' => $this->moduleDataSetup,
            ]
        );
    }

    public function testApply()
    {
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willReturn($this->mysqlInterceptor);
        $this->moduleDataSetup->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->groupFactory->expects($this->any())->method('create')->willReturn($this->group);
        $this->group->expects($this->any())->method('load')->willReturnSelf();
        $this->group->expects($this->any())->method('getWebsiteId')->willReturn(1);
        $this->group->expects($this->any())->method('getRootCategoryId')->willReturn(2);
        $this->group->expects($this->any())->method('getDefaultStoreId')->willReturn(2);
        $this->mysqlInterceptor->expects($this->any())->method('insertArray')->willReturn(2);
        $this->mysqlInterceptor->expects($this->any())->method('update')->willReturn(3);
        $this->moduleDataSetup
            ->method('getTable')
            ->withConsecutive(
                ['store_group'],
                ['store'],
                ['store_group']
            )
            ->willReturnOnConsecutiveCalls(
                'store_group',
                'store',
                'store_group'
            );
        $this->moduleDataSetup->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->createStore->apply());
    }

    public function testGetDependencies()
    {
        $this->assertIsArray($this->createStore->getDependencies());
    }

    public function testGetAliases()
    {
        $this->assertIsArray($this->createStore->getAliases());
    }

    public function testGetVersion()
    {
        $this->assertEquals('1.0.0', $this->createStore->getVersion());
    }

}
