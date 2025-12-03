<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerGroup\Test\Unit\Block\Adminhtml\Group;

use Fedex\CustomerGroup\Block\Adminhtml\Group\Edit;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Group\Edit as MagEdit;
use PHPUnit\Framework\TestCase;

class EditTest extends TestCase
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var GroupManagementInterface
     */
    protected $groupManagement;

    /**
     * @var ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Edit
     */
     protected $edit;

    /**
     * used to set the values to variables or objects.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->coreRegistry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();
        $this->groupRepository = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','getCode'])
            ->getMockForAbstractClass();
        $this->groupManagement = $this->getMockBuilder(GroupManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->edit = $this->objectManagerHelper->getObject(
            Edit::class,
            [
                'coreRegistry' => $this->coreRegistry,
                'groupRepository' => $this->groupRepository,
                'groupManagement' => $this->groupManagement
            ]
        );
    }
    
    /**
     * Test method for getHeaderText
     *
     * @return string
     */
    public function testGetHeaderText()
    {
        $this->assertNotNull($this->edit->getHeaderText());
    }

    /**
     * Test method for getHeaderText
     *
     * @return string
     */
    public function testGetHeaderTextWithElse()
    {
        $this->coreRegistry->expects($this->any())->method('registry')->willReturn('111');
        $this->groupRepository->expects($this->any())->method('getById')->willReturnSelf();
        $this->groupRepository->expects($this->any())->method('getCode')->willReturn('111');
        $this->assertNotNull($this->edit->getHeaderText());
    }

    /**
     * Test method for getHeaderCssClass
     *
     * @return string
     */
    public function testGetHeaderCssClass()
    {
        $this->assertNotNull($this->edit->getHeaderCssClass());
    }
}
