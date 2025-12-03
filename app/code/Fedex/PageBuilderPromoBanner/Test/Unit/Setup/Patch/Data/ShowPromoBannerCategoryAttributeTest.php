<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\PageBuilderPromoBanner\Test\Unit\Setup\Patch\Data;

use Fedex\PageBuilderPromoBanner\Setup\Patch\Data\ShowPromoBannerCategoryAttribute;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;

class ShowPromoBannerCategoryAttributeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @var ShowPromoBannerCategoryAttribute $showPromoBannerCategoryAttribute
     */
    private $showPromoBannerCategoryAttribute;

    /**
     * Test setup
     */
    public function setUp(): void
    {
        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['create', 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->showPromoBannerCategoryAttribute = $this->objectManager->getObject(
            ShowPromoBannerCategoryAttribute::class,
            [
                'eavSetupFactory' => $this->eavSetupFactory
            ]
        );
    }

    /**
     * Test apply function
     *
     * @return void
     */
    public function testApply()
    {
        $attributeData = [
            'type' => 'int',
            'label' => 'Show Promo Banner',
            'input' => 'boolean',
            'default' => true,
            'sort_order' => 5,
            'source' => Boolean::class,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'General Information',
            'visible_on_front' => true
        ];
        $attributeCode = 'show_promo_banner';
        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())
            ->method('addAttribute')->with(Category::ENTITY, $attributeCode, $attributeData)
            ->willReturnSelf();
        $this->assertEquals(null, $this->showPromoBannerCategoryAttribute->apply());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->showPromoBannerCategoryAttribute->getDependencies());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->showPromoBannerCategoryAttribute->getAliases());
    }
}
