<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\ProductCustomAtrribute\Test\Unit\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use \Fedex\ProductCustomAtrribute\Setup\Patch\Data\InsertProductAdditionalImageRole;
use PHPUnit\Framework\TestCase;

/**
 * Test class InsertProductAdditionalImageRoleTest
 */
class InsertProductAdditionalImageRoleTest extends TestCase
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
     * @var InsertProductAdditionalImageRole $insertProductAdditionalImageRole
     */
    private $insertProductAdditionalImageRole;

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

        $this->insertProductAdditionalImageRole = $this->objectManager->getObject(
            InsertProductAdditionalImageRole::class,
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
        $this->eavSetupFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->eavSetupFactory->expects($this->any())->method('addAttribute')->willReturnSelf();
        $this->assertEquals(null, $this->insertProductAdditionalImageRole->apply());
    }

    /**
     * Test getAliases function
     *
     * @return array
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->insertProductAdditionalImageRole->getAliases());
    }

    /**
     * Test getDependencies function
     *
     * @return array
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->insertProductAdditionalImageRole->getDependencies());
    }
}
