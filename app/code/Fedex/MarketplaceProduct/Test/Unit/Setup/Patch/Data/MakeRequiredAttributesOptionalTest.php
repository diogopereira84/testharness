<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Fedex\MarketplaceProduct\Setup\Patch\Data\MakeRequiredAttributesOptional;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MakeRequiredAttributesOptionalTest extends TestCase
{
    private const ATTRIBUTES = [
        'visible_attributes', 'quantity', 'quantity_1'
    ];

    /**
     * @var ObjectManager
     */
    private $objectManager;

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

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavAttribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test updateAttributes method.
     *
     * @return void
     */
    public function testUpdateAttributes()
    {
        $patch = $this->objectManager->getObject(MakeRequiredAttributesOptional::class, [
            'moduleDataSetup' => $this->moduleDataSetup,
            'eavSetupFactory' => $this->eavSetupFactory,
            'logger' => $this->logger,
            'eavAttribute' => $this->eavAttribute,
        ]);

        $eavSetup = $this->getMockBuilder(EavSetup::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactory->expects($this->any())
            ->method('create')
            ->willReturn($eavSetup);

        $eavSetup->expects($this->any())
            ->method('updateAttribute')
            ->withConsecutive(
                [Product::ENTITY, 'visible_attributes', ['is_required' => false]],
                [Product::ENTITY, 'quantity', ['is_required' => false]],
                [Product::ENTITY, 'quantity_1', ['is_required' => false]]
            );

        $patch->updateAttributes();
    }

    /**
     * Test getAliases method()
     *
     * @return void
     */
    public function testGetAliases()
    {
        $patch = $this->objectManager->getObject(MakeRequiredAttributesOptional::class, [
            'moduleDataSetup' => $this->moduleDataSetup,
            'eavSetupFactory' => $this->eavSetupFactory,
            'logger' => $this->logger,
            'eavAttribute' => $this->eavAttribute,
        ]);

        $this->assertEquals([], $patch->getAliases());
    }

    /**
     * Test getDependencies method()
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $patch = $this->objectManager->getObject(MakeRequiredAttributesOptional::class, [
            'moduleDataSetup' => $this->moduleDataSetup,
            'eavSetupFactory' => $this->eavSetupFactory,
            'logger' => $this->logger,
            'eavAttribute' => $this->eavAttribute,
        ]);

        $this->assertEquals([], $patch->getDependencies());
    }
}
