<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateAttributesToNotSyncToMirakl;
use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateMapSkuAttribute;
use Fedex\Catalog\Setup\Patch\Data\CreateAboutThisProductAttributes;

class UpdateAttributesToNotSyncToMiraklTest extends TestCase
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private EavSetupFactory $eavSetupFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Attribute
     */
    private Attribute $eavAttribute;

    /**
     * @var UpdateAttributesToNotSyncToMirakl
     */
    private UpdateAttributesToNotSyncToMirakl $patch;

    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->eavSetupFactory = $this->createMock(EavSetupFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eavAttribute = $this->createMock(Attribute::class);

        $this->patch = new UpdateAttributesToNotSyncToMirakl(
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
        $eavSetup = $this->createMock(EavSetup::class);
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetup])
            ->willReturn($eavSetup);

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));

        $this->eavAttribute->expects($this->exactly(3))
            ->method('getIdByCode')
            ->withConsecutive(
                [Product::ENTITY, 'map_sku'],
                [Product::ENTITY, 'meta_keyword'],
                [Product::ENTITY, 'shipping_estimator_content_alert_new']
            )
            ->willReturnOnConsecutiveCalls(1, 2, 3);

        $eavSetup->expects($this->exactly(3))
            ->method('updateAttribute')
            ->withConsecutive(
                [Product::ENTITY, 'map_sku', ['mirakl_is_exportable' => false]],
                [Product::ENTITY, 'meta_keyword', ['mirakl_is_exportable' => false]],
                [Product::ENTITY, 'shipping_estimator_content_alert_new', ['mirakl_is_exportable' => false]]
            );

        $this->logger->expects($this->never())
            ->method('error');

        $this->patch->apply();
    }

    /**
     * Test getDependencies method.
     *
     * @return void
     */
    public function testGetDependencies(): void
    {
        $dependencies = $this->patch->getDependencies();

        $this->assertSame($dependencies, [UpdateMapSkuAttribute::class,
            CreateAboutThisProductAttributes::class]);
    }

    /**
     * Test getAliases method.
     *
     * @return void
     */
    public function testGetAliases(): void
    {
        $aliases = $this->patch->getAliases();

        $this->assertSame($this->patch->getDependencies(), $aliases);
    }
}
