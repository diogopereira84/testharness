<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SSO\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Fedex\SSO\Setup\Patch\Data\InsertCustomerContactNumberAttribute;
use PHPUnit\Framework\TestCase;

/**
 * Test class for InsertCustomerContactNumberAttribute
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class InsertCustomerContactNumberAttributeTest extends TestCase
{

    protected $insertCustomerContactNumberAttribute;
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;

    /**
     * @var CustomerSetupFactory $customerSetupFactory
     */
    protected $customerSetupFactory;

    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->customerSetupFactory = $this->getMockBuilder(CustomerSetupFactory::class)
            ->setMethods(['create', 'addAttribute', 'getEavConfig', 'getAttribute', 'addData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->insertCustomerContactNumberAttribute = $this->objectManager->getObject(
            InsertCustomerContactNumberAttribute::class,
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
        $this->customerSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->customerSetupFactory->expects($this->any())->method('getEavConfig')->willReturnSelf();
        $this->customerSetupFactory->expects($this->any())->method('getAttribute')->willReturnSelf();
        $this->customerSetupFactory->expects($this->any())->method('addData')->willReturnSelf();
        $this->customerSetupFactory->expects($this->any())->method('save')->willReturnSelf();
        
        $this->assertEquals(null, $this->insertCustomerContactNumberAttribute->apply());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->insertCustomerContactNumberAttribute->getDependencies());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->insertCustomerContactNumberAttribute->getAliases());
    }
}
