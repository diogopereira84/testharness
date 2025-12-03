<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
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

class AddCtaButtonAttributeTest extends TestCase
{
    private const CTA_VALUE_ATTRIBUTE = 'cta_value';

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
     * @var AddCtaButtonAttribute
     */
    private $addCtaButtonAttribute;

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

        $this->addCtaButtonAttribute = new AddCtaButtonAttribute(
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
                self::CTA_VALUE_ATTRIBUTE
            );

        $eavSetupFactoryObject->expects($this->once())
            ->method('addAttribute')
            ->with(
                Product::ENTITY,
                self::CTA_VALUE_ATTRIBUTE,
                [
                    'group' => 'Mirakl Marketplace',
                    'type' => 'varchar',
                    'label' => 'CTA Button Value',
                    'input' => 'text',
                    'sort_order' => 0,
                    'default' => 'Explore Options',
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
                    'used_in_product_listing' => false,
                ]
            );

        $this->addCtaButtonAttribute->apply();
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
                self::CTA_VALUE_ATTRIBUTE
            );

        $this->addCtaButtonAttribute->revert();
    }

    /**
     * Test getAliases method()
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->addCtaButtonAttribute->getAliases());
    }

    /**
     * Test getDependencies method()
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], AddCtaButtonAttribute::getDependencies());
    }
}
