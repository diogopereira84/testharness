<?php
/**
 * @category     Fedex
 * @package      Fedex_MarketplaceProduct
 * @copyright    Copyright (c) 2023 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
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
use Fedex\MarketplaceProduct\Setup\Patch\Data\UpdateNavitorIsCategoryAttributeToCategoryPunchout;
use Fedex\MarketplaceProduct\Setup\Patch\Data\AddNavitorIsCategoryAttribute;

class UpdateNavitorIsCategoryAttributeToCategoryPunchoutTest extends TestCase
{
    private const OLD_ATTRIBUTE_CODE = 'navitor_is_category';
    private const NEW_ATTRIBUTE_CODE = 'category_punchout';

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
                self::OLD_ATTRIBUTE_CODE,
                'attribute_code',
                self::NEW_ATTRIBUTE_CODE
            );

        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->with(['setup' => $this->moduleDataSetup])
            ->willReturn($eavSetup);

        $this->eavAttribute->expects($this->once())
            ->method('getIdByCode')
            ->with(Product::ENTITY, self::OLD_ATTRIBUTE_CODE)
            ->willReturn(123);

        $this->moduleDataSetup->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class));

        $patch = new UpdateNavitorIsCategoryAttributeToCategoryPunchout(
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
        $patch = new UpdateNavitorIsCategoryAttributeToCategoryPunchout(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([AddNavitorIsCategoryAttribute::class], $patch->getDependencies());
    }

    /**
     * Test getAliases method()
     *
     * @return void
     */
    public function testGetAliases(): void
    {
        $patch = new UpdateNavitorIsCategoryAttributeToCategoryPunchout(
            $this->moduleDataSetup,
            $this->eavSetupFactory,
            $this->logger,
            $this->eavAttribute
        );

        $this->assertEquals([AddNavitorIsCategoryAttribute::class], $patch->getAliases());
    }
}
