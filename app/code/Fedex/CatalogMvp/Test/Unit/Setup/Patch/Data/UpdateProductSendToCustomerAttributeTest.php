<?php

namespace Fedex\CatalogMvp\Test\Unit\Setup\Patch\Data;

use Fedex\CatalogMvp\Setup\Patch\Data\UpdateProductSendToCustomerAttribute;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class UpdateProductSendToCustomerAttributeTest extends TestCase
{

    protected $moduleDataSetupMock;
    protected $eavSetupFactoryMock;
    protected $eavSetupMock;
    protected $adapterMock;
    protected $updateUpdatedAtAttributeMock;
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupMock = $this->getMockBuilder(EavSetup::class)
            ->setMethods(['updateAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['startSetup', 'endSetup'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->updateUpdatedAtAttributeMock = $objectManagerHelper->getObject(
            UpdateProductSendToCustomerAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock
            ]
        );
    }

    public function testApply()
    {
        $this->adapterMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturn($this->adapterMock);
        $this->eavSetupFactoryMock->expects($this->once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetupMock])
            ->willReturn($this->eavSetupMock);

        $this->eavSetupMock->expects($this->any())
            ->method('updateAttribute')
            ->willReturnSelf();

        $this->adapterMock->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->updateUpdatedAtAttributeMock->apply();
    }

    public function testGetDependencies()
    {

        $this->assertEquals([
            \Fedex\CatalogMvp\Setup\Patch\Data\InsertProductSendToCustomerAttribute::class
        ], $this->updateUpdatedAtAttributeMock->getDependencies());
    }

    public function testGetAliases()
    {
        $this->assertEquals([], $this->updateUpdatedAtAttributeMock->getAliases());
    }
}
