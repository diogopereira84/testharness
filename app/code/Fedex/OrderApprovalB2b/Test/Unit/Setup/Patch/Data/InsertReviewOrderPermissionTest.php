<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Fedex\OrderApprovalB2b\Test\Unit\Setup\Patch\Data;

use Fedex\OrderApprovalB2b\Setup\Patch\Data\InsertReviewOrderPermission;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;

/**
 * Test class for InsertReviewOrderPermissionTest
 *
 */
class InsertReviewOrderPermissionTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var EnhanceRolePermissionFactory $enhanceRolePermissionFactory
     */
    protected $enhanceRolePermissionFactory;

    /**
     * @var InsertReviewOrderPermission $insertReviewOrderPermission
     */
    protected $insertReviewOrderPermission;

    /**
     * Test setup
     */
    protected function setUp(): void
    {
        $this->enhanceRolePermissionFactory = $this->getMockBuilder(EnhanceRolePermissionFactory::class)
            ->setMethods(['create', 'setData', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->insertReviewOrderPermission = $this->objectManager->getObject(
            InsertReviewOrderPermission::class,
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
       
        $this->assertEquals(null, $this->insertReviewOrderPermission->apply());
    }

    /**
     * Test getDependencies function
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertEquals([], $this->insertReviewOrderPermission->getDependencies());
    }

    /**
     * Test getAliases function
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertEquals([], $this->insertReviewOrderPermission->getAliases());
    }
}
