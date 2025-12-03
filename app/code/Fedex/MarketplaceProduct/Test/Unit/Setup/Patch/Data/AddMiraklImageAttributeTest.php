<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Setup\Patch\Data;

use Fedex\MarketplaceProduct\Setup\Patch\Data\AddMiraklImageAttribute;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Eav\Setup\EavSetupFactory;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Psr\Log\LoggerInterface;

class AddMiraklImageAttributeTest extends TestCase
{
    /**
     * @var AddMiraklImageAttribute
     */
    private $addMiraklImageAttribute;

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

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['removeAttribute','addAttribute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addMiraklImageAttribute = $this->objectManager->getObject(
            AddMiraklImageAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetup,
                'eavSetupFactory' => $this->eavSetupFactory,
                'logger' => $this->logger
            ]
        );
    }

    public function testApply()
    {
        $eavSetupFactoryObject = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['removeAttribute','addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->moduleDataSetup);

        foreach (AddMiraklImageAttribute::MIRAKL_IMAGE_ATTRIBUTES as $image_code) {
            $eavSetupFactoryObject->expects($this->any())
                ->method('removeAttribute')
                ->with(
                    Product::ENTITY,
                    $image_code
                );
        }

        $attributeData = [
            'group'                   => 'Mirakl Marketplace',
            'type'                    => 'varchar',
            'label'                   => 'Mirakl Image',
            'input'                   => 'text',
            'global'                  => ScopedAttributeInterface::SCOPE_GLOBAL,
            'visible'                 => true,
            'required'                => false,
            'user_defined'            => true,
            'searchable'              => false,
            'filterable'              => false,
            'comparable'              => false,
            'visible_on_front'        => false,
            'unique'                  => false,
            'apply_to'                => 'simple',
            'is_configurable'         => false,
            'used_in_product_listing' => false,
        ];

        foreach (AddMiraklImageAttribute::MIRAKL_IMAGE_ATTRIBUTES as $image_code) {
            $eavSetupFactoryObject->expects($this->any())
                ->method('addAttribute')
                ->with(
                    Product::ENTITY,
                    $image_code,
                    $attributeData
                );
        }
        $this->addMiraklImageAttribute->apply();
    }

    public function testRevert()
    {
        $eavSetupFactoryObject = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['removeAttribute','addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavSetupFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->moduleDataSetup);

        foreach (AddMiraklImageAttribute::MIRAKL_IMAGE_ATTRIBUTES as $image_code) {
            $eavSetupFactoryObject->expects($this->any())
                ->method('removeAttribute')
                ->with(
                    Product::ENTITY,
                    $image_code
                );
        }

        $this->moduleDataSetup->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->getMockBuilder(ModuleDataSetupInterface::class)
                ->disableOriginalConstructor()
                ->getMock());

        $this->addMiraklImageAttribute->revert();
    }
}
