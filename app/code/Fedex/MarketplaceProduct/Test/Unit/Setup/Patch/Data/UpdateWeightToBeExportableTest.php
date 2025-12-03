<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateWeightToBeExportable;
use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class UpdateWeightToBeExportableTest extends TestCase
{
    private const WEIGHT_ATTRIBUTE = 'weight';

    /**
     * @var ModuleDataSetupInterface
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
     * @var Attribute
     */
    private $eavAttribute;

    /**
     * @var UpdateWeightToBeExportable
     */
    private $patch;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eavAttribute = $this->createMock(Attribute::class);

        $this->patch = new UpdateWeightToBeExportable(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testApply(): void
    {
        $attrProduct = 123; // Attribute ID
        $setup = $this->createMock(EavSetup::class);
        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetup])
            ->willReturn($setup);
        $this->eavAttribute->expects($this->once())
            ->method('getIdByCode')
            ->with(Product::ENTITY, self::WEIGHT_ATTRIBUTE)
            ->willReturn($attrProduct);
        $setup->expects($this->once())
            ->method('updateAttribute')
            ->with(
                Product::ENTITY,
                self::WEIGHT_ATTRIBUTE,
                ['mirakl_is_exportable' => true]
            );

        $this->patch->apply();
    }

    /**
     * Test getDependencies
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->patch = new UpdateWeightToBeExportable(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([], $this->patch->getDependencies());
    }

    /**
     * Test getAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->patch = new UpdateWeightToBeExportable(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([], $this->patch->getAliases());
    }
}
