<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Fedex\SelfReg\Ui\Component\Listing\Column\UserGroup;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\ViewModel\CompanyUser;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use Magento\Framework\Phrase;

class UserGroupTest extends \PHPUnit\Framework\TestCase
{
    protected $contextInterfaceMock;
    protected $companyUserMock;
    protected $processorMock;
    protected $userGroupMock;
    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextInterfaceMock = $this->getMockBuilder(ContextInterface::class)
            ->disableOriginalConstructor()->setMethods(['getProcessor'])
            ->getMockForAbstractClass();

        $this->companyUserMock = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['toggleUserGroupAndFolderLevelPermissions'])
            ->getMock();

        $this->processorMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
            ->disableOriginalConstructor()->setMethods(['register'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->userGroupMock = $objectManagerHelper->getObject(
            UserGroup::class,
            [
                'companyUser' => $this->companyUserMock,
                'context' => $this->contextInterfaceMock,
            ]
        );
    }
    
    public function testPrepare()
    {
        $this->contextInterfaceMock->expects($this->any())->method('getProcessor')->willReturn($this->processorMock);
        $this->companyUserMock->expects($this->any())
            ->method('toggleUserGroupAndFolderLevelPermissions')
            ->willReturn(true);
        $expectedResult = $this->userGroupMock->prepare();
        $this->assertNull($expectedResult);
    }
}
