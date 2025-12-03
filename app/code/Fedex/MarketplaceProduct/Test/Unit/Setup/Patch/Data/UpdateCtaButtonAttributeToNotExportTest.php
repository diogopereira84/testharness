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
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class UpdateCtaButtonAttributeToNotExportTest extends TestCase
{
    private const CTA_VALUE_ATTRIBUTE = 'cta_value';

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
        $this->eavAttribute = $this->createMock(Attribute::class);
    }

    /**
     * Test apply method()
     *
     * @return void
     */
    public function testApply(): void
    {
        $eavSetup = $this->createMock(EavSetup::class);
        $eavSetup->expects($this->once())
            ->method('updateAttribute')
            ->with(
                Product::ENTITY,
                self::CTA_VALUE_ATTRIBUTE,
                ['mirakl_is_exportable' => false]
            );

        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetup])
            ->willReturn($eavSetup);

        $this->eavAttribute->expects($this->once())
            ->method('getIdByCode')
            ->with(Product::ENTITY, self::CTA_VALUE_ATTRIBUTE)
            ->willReturn(123);

        $this->moduleDataSetup->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));

        $patch = new UpdateCtaButtonAttributeToNotExport(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $patch->apply();
    }

    /**
     * Test getDependencies method()
     *
     * @return void
     */
    public function testGetDependencies(): void
    {
        $patch = new UpdateCtaButtonAttributeToNotExport(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([AddCtaButtonAttribute::class], $patch->getDependencies());
    }

    /**
     * Test getAliases method()
     *
     * @return void
     */
    public function testGetAliases(): void
    {
        $patch = new UpdateCtaButtonAttributeToNotExport(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([AddCtaButtonAttribute::class], $patch->getAliases());
    }
}
