<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class AddNavitorIsCategoryAttributeTest extends TestCase
{
    private const ATTRIBUTE_CODE = 'navitor_is_category';

    /**
     * @var (ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AddNavitorIsCategoryAttribute
     */
    private $addNavitorIsCategoryAttribute;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['removeAttribute','addAttribute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['removeAttribute','addAttribute','create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->addNavitorIsCategoryAttribute = new AddNavitorIsCategoryAttribute(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger
        );
    }

    /**
     * Test Apply method()
     *
     * @return void
     */
    public function testApply()
    {
        $eavSetupFactoryObject = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['removeAttribute','addAttribute','create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->willReturn($eavSetupFactoryObject);

        $eavSetupFactoryObject->expects($this->once())
            ->method('removeAttribute')
            ->with(
                Product::ENTITY,
                self::ATTRIBUTE_CODE
            );

        $eavSetupFactoryObject->expects($this->once())
            ->method('addAttribute')
            ->with(
                Product::ENTITY,
                self::ATTRIBUTE_CODE,
                [
                    'group' => 'Mirakl Marketplace',
                    'type' => 'int',
                    'label' => 'Navitor Is Category',
                    'input' => 'boolean',
                    'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
                    'sort_order' => 10,
                    'default' => null,
                    'global' => ScopedAttributeInterface::SCOPE_STORE,
                    'visible' => true,
                    'required' => false,
                    'user_defined' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'comparable' => false,
                    'visible_on_front' => false,
                    'unique' => false,
                    'apply_to' => 'simple',
                    'is_configurable' => false,
                    'used_in_product_listing' => true,
                    'mirakl_is_exportable'    => true
                ]
            );

        $this->addNavitorIsCategoryAttribute->apply();
    }

    /**
     * Test Revert method()
     *
     * @return void
     */
    public function testRevert()
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects($this->once())
            ->method('startSetup');

        $connection->expects($this->once())
            ->method('endSetup');

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $eavSetupFactoryObject = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['removeAttribute','addAttribute','create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->willReturn($eavSetupFactoryObject);

        $eavSetupFactoryObject->expects($this->once())
            ->method('removeAttribute')
            ->with(
                Product::ENTITY,
                self::ATTRIBUTE_CODE
            );

        $this->addNavitorIsCategoryAttribute->revert();
    }

    /**
     * Test getAliases method()
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->addNavitorIsCategoryAttribute->getAliases());
    }

    /**
     * Test getDependencies method()
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], AddNavitorIsCategoryAttribute::getDependencies());
    }
}
