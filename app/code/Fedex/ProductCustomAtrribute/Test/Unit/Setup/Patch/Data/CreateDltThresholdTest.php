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
use Fedex\ProductCustomAtrribute\Setup\Patch\Data\CreateDltThreshold;
 
class CreateDltThresholdTest extends TestCase
{

    protected $dltThresholdMocks;
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $dltThresholdMock;
 
   /**
     * Test setup
    */
    public function setUp(): void
    {
        $this->moduleDataSetupInterfaceMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactoryMock = $this->getMockBuilder(EavSetupFactory::class)
        ->setMethods(
            [
                'create',
                'addAttribute',
                'getEntityTypeId',
                'getAttributeSetId',
                'getAttributeGroupId',
                'addAttributeToGroup'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->dltThresholdMocks = $this->getMockForAbstractClass(
            CreateDltThreshold::class,
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
        ->method('addAttribute')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getEntityTypeId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeSetId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('getAttributeGroupId')
        ->willReturnSelf();
        $this->eavSetupFactoryMock->expects($this->any())
        ->method('addAttributeToGroup')
        ->willReturnSelf();
        $this->assertEquals(null,$this->dltThresholdMocks->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->dltThresholdMocks->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->dltThresholdMocks->getDependencies());
    }
}
