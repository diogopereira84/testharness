<?php
declare(strict_types=1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\ProductCustomAtrribute\Setup\Patch\Data\UpdateDltThreshold;
 
class UpdateDltThresholdTest extends TestCase
{
    protected $adapterInterface;
    protected $updateDltThresholdMocks;
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $updatedltThresholdMock;
 
   /**
     * Test setup
    */
    public function setUp(): void
    {
        $this->moduleDataSetupInterfaceMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getConnection', 'getTable','endSetup','startSetup'])
            ->getMockForAbstractClass();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
        ->setMethods(
            [
                'create',
                'addAttribute',
                'getAttributeId',
                'getAttributeSetId'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();
       
        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->updateDltThresholdMocks = $this->getMockForAbstractClass(
            UpdateDltThreshold::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
            ]
        );
    }

    /**
     * Test apply function
     */
    public function testapply()
    {
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('create')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeId')
        ->willReturn('dlt_thresholds');
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeSetId')
        ->willReturn('12');
        $this->moduleDataSetupInterfaceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);
        $this->moduleDataSetupInterfaceMock->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->moduleDataSetupInterfaceMock->expects($this->any())
            ->method('getTable')
            ->willReturn('eav_entity_attribute');
        $this->adapterInterface->expects($this->any())
            ->method('delete')
            ->willReturnSelf();
       $this->moduleDataSetupInterfaceMock->expects($this->any())->method('endSetup')->willReturnSelf();
       $this->assertEquals(null,$this->updateDltThresholdMocks->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->updateDltThresholdMocks->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([\Fedex\ProductCustomAtrribute\Setup\Patch\Data\CreateDltThreshold::class], $this->updateDltThresholdMocks->getDependencies());
    }
}