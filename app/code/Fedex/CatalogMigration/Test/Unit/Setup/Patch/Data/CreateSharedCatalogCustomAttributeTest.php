<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\CatalogMigration\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\CatalogMigration\Setup\Patch\Data\CreateSharedCatalogCustomAttribute;
use Fedex\CatalogMigration\Model\Entity\Attribute\Source\SharedCatalogOptions;
 
class CreateSharedCatalogCustomAttributeTest extends TestCase
{

    protected $adapterInterface;
    protected $sharedCatalogOptions;
    protected $moduleDataSetupInterfaceMock;
    protected $eavSetupFactoryMock;
    protected $customDocsAttributeMock;
 
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
                'getAttributeGroupId',
                'getEntityTypeId',
                'getAllAttributeSetIds',
                'addAttributeToGroup'
            ]
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->adapterInterface = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['delete'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->sharedCatalogOptions = $this->getMockBuilder(SharedCatalogOptions::class)
            ->setMethods(['getAllOptions'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->customDocsAttributeMock = $this->getMockForAbstractClass(
            CreateSharedCatalogCustomAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupInterfaceMock,
                'eavSetupFactory' => $this->eavSetupFactoryMock,
                'sharedCatalogOptions' => $this->sharedCatalogOptions
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
            ->method('getAllAttributeSetIds')
            ->willReturn(['12']);
        $this->moduleDataSetupInterfaceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->adapterInterface);
        $this->sharedCatalogOptions->expects($this->any())->method('getAllOptions')->willReturn([['value'=>'']]);
        $this->assertEquals(null,$this->customDocsAttributeMock->apply());
    }

    /**
     * Test getAliases function
     */
    public function testgetAliases()
    {
        $this->assertEquals([], $this->customDocsAttributeMock->getAliases());
    }

    /**
     * Test getDependencies function
     */
    public function testgetDependencies()
    {
        $this->assertEquals([], $this->customDocsAttributeMock->getDependencies());
    }
}
