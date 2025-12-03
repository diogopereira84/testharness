<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Fedex\SSO\Setup\Patch\Data\UpgradeCustomerAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Test class for UpgradeCustomerAttribute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class UpgradeCustomerAttributeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var Config $eavConfig
     */
    protected $eavConfig;

    /**
     * @var EavSetupFactory $eavSetupFactory
     */
    protected $eavSetupFactory;

    /**
     * @var AttributeSetFactory $attributeSetFactory
     */
    protected $attributeSetFactory;

    /**
     * @var UpgradeCustomerAttribute $upgradeCustomerAttribute
     */
    protected $upgradeCustomerAttribute;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->eavConfig = $this->getMockBuilder(Config::class)
            ->setMethods([
                            'getEntityType',
                            'getAttribute',
                            'getEavConfig',
                            'addData',
                            'save',
                            'getDefaultAttributeSetId'
                        ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->eavSetupFactory = $this->getMockBuilder(EavSetupFactory::class)
            ->setMethods(['create', 'addAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeSetFactory = $this->getMockBuilder(AttributeSetFactory::class)
            ->setMethods(['create', 'getDefaultGroupId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->upgradeCustomerAttribute = $this->objectManager->getObject(
            UpgradeCustomerAttribute::class,
            [
                'eavConfig' => $this->eavConfig,
                'eavSetupFactory' => $this->eavSetupFactory,
                'attributeSetFactory' => $this->attributeSetFactory
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
        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavConfig->expects($this->any())->method('getEntityType')->willReturnSelf();
        $this->eavConfig->expects($this->any())->method('getDefaultAttributeSetId')->willReturnSelf();
        $this->attributeSetFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->attributeSetFactory->expects($this->any())->method('getDefaultGroupId')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->eavConfig->expects($this->any())->method('getAttribute')->willReturnSelf();
        $this->assertEquals(null, $this->upgradeCustomerAttribute->apply());
    }

    /**
     * Test getAliases function
     * 
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->upgradeCustomerAttribute->getAliases());
    }

    /**
     * Test getDependencies function
     * 
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->upgradeCustomerAttribute->getDependencies());
    }
}
