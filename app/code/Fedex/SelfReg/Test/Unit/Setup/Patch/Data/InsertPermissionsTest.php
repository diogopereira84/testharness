<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\SelfReg\Test\Unit\Setup\Patch\Data;

use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\Setup\Patch\Data\InsertPermissions;
use PHPUnit\Framework\TestCase;

/**
 * Test class for InsertPermissions
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class InsertPermissionsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $insertPermissions;
    /**
     * @var EnhanceRolePermissionFactory $enhanceRolePermissionFactory
     */
    protected $enhanceRolePermissionFactory;

    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->enhanceRolePermissionFactory = $this->getMockBuilder(EnhanceRolePermissionFactory::class)
            ->setMethods(['create', 'setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->insertPermissions = $this->objectManager->getObject(
            InsertPermissions::class,
            [
                'enhanceRolePermission' => $this->enhanceRolePermissionFactory
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
        $this->enhanceRolePermissionFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->enhanceRolePermissionFactory->expects($this->any())->method('setData')->willReturnSelf();
        $this->enhanceRolePermissionFactory->expects($this->any())->method('save')->willReturnSelf();
        $this->assertEquals(null, $this->insertPermissions->apply());
    }

    /**
     * Test getDependencies function
     * 
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->insertPermissions->getDependencies());
    }

    /**
     * Test getAliases function
     * 
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->insertPermissions->getAliases());
    }
}
