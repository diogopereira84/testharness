<?php

declare(strict_types=1);

use Fedex\CatalogMvp\Setup\Patch\Data\UpdateDefaultSortByConfig;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpdateDefaultSortByConfigTest extends TestCase
{
    protected $moduleDataSetupMock;
    protected $configWriterMock;
    protected $adapterMock;
    protected $updateDefaultSortByConfigMock;
    private const CATALOG_FRONTEND_DEFAULT_SORT_BY = 'catalog/frontend/default_sort_by';
    private const DEFAULT_SORT_BY_NAME = 'name';
    private const DEFAULT_SORT_BY_UPDATED_AT = 'updated_at';

    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->configWriterMock = $this->getMockBuilder(WriterInterface::class)
            ->setMethods(['save'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['startSetup','endSetup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->updateDefaultSortByConfigMock = $objectManagerHelper->getObject(
            UpdateDefaultSortByConfig::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'configWriter' => $this->configWriterMock
            ]
        );
    }

    public function testApply()
    {
        $this->adapterMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturn($this->adapterMock);
        $this->configWriterMock->expects($this->any())->method('save')->with(
            self::CATALOG_FRONTEND_DEFAULT_SORT_BY,
            self::DEFAULT_SORT_BY_UPDATED_AT,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->adapterMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->updateDefaultSortByConfigMock->apply());
    }

    public function testRevert()
    {
        $this->adapterMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturn($this->adapterMock);
        $this->configWriterMock->expects($this->any())->method('save')->with(
            self::CATALOG_FRONTEND_DEFAULT_SORT_BY,
            self::DEFAULT_SORT_BY_NAME,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->adapterMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertEquals(null, $this->updateDefaultSortByConfigMock->revert());
    }

    public function testGetDependencies()
    {
        $this->assertEquals([], $this->updateDefaultSortByConfigMock->getDependencies());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->updateDefaultSortByConfigMock->getAliases());
    }
}
