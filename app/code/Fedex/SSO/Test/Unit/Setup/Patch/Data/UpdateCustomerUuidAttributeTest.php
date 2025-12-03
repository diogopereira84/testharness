<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SSO\Setup\Patch\Data\UpdateCustomerUuidAttribute;

/**
 * Test class for UpdateCustomerUuidAttributeTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class UpdateCustomerUuidAttributeTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $updateCustomerUuidAttribute;
    /**
     * @var ModuleDataSetupInterface
     */
    protected $moduleDataSetup;

    /**
     * @var CustomerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->customerSetupFactory = $this->getMockBuilder(CustomerSetupFactory::class)
            ->setMethods(['create', 'updateAttribute'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->updateCustomerUuidAttribute = $this->objectManager->getObject(
            UpdateCustomerUuidAttribute::class,
            [
                'moduleDataSetup' => $this->moduleDataSetup,
                'customerSetupFactory' => $this->customerSetupFactory
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
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->moduleDataSetup->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->customerSetupFactory->expects($this->once())->method('create')->willReturnSelf();
        $this->customerSetupFactory->expects($this->any())->method('updateAttribute')->willReturnSelf();
        $this->assertNull($this->updateCustomerUuidAttribute->apply());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->updateCustomerUuidAttribute->getDependencies());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->updateCustomerUuidAttribute->getAliases());
    }
}
