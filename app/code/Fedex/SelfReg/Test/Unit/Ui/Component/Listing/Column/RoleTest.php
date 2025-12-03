<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Ui\Component\Listing\Column;

use Fedex\SelfReg\Ui\Component\Listing\Column\Role;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\ReturnStub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\ViewModel\CompanyUser;

class RoleTest extends TestCase
{
    protected $contextInterfaceMock;
    /**
     * @var (\Magento\Framework\View\Element\UiComponentFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $uiComponentFactoryMock;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    /**
     * @var (\Fedex\SelfReg\Helper\SelfReg & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $selfReg;
    protected $companyUserMock;
    protected $processorMock;
    protected $roleMock;
    /**
     * @var array<string, string>
     */
    protected $data;
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

        $this->uiComponentFactoryMock = $this->getMockBuilder(UiComponentFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSelfRegCustomer', 'isSelfRegCustomerAdmin'])
            ->getMock();

        $this->companyUserMock = $this->getMockBuilder(CompanyUser::class)
            ->disableOriginalConstructor()
            ->setMethods(['toggleCustomerRolesAndPermissions','toggleUserGroupAndFolderLevelPermissions'])
            ->getMock();

        $this->processorMock = $this->getMockBuilder(\Magento\Framework\View\Element\UiComponent\Processor::class)
				->disableOriginalConstructor()->setMethods(['register'])
				->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);

        $this->roleMock = $objectManagerHelper->getObject(
            Role::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'selfReg' => $this->selfReg,
                'companyUser' => $this->companyUserMock,
                'context' => $this->contextInterfaceMock
            ]
        );
    }

    public function testPrepare()
    {
		 $this->contextInterfaceMock->expects($this->any())->method('getProcessor')->willReturn($this->processorMock);
		 $this->companyUserMock->expects($this->any())->method('toggleUserGroupAndFolderLevelPermissions')->willReturn(true);
		 $expectedResult = $this->roleMock->prepare();
		 $this->assertNull($expectedResult);
    }

    /**
     * unit test for role name when user role permission toogle is on
     *
     * @param array $dataSource
     * @return array
     */
    public function testPrepareDataSourcetrue()
    {
        $this->data['name'] = "Users | Edit";
        $this->setName('name', 'role_name');
        //~ $this->name = 'email12';
        $this->companyUserMock->expects($this->any())->method('toggleCustomerRolesAndPermissions')->willReturn(true);
        $testData = ['data' => ['items' => [['role_name' => 'Company Administrator']]]];
        $expectedResult = ['data' => ['items' => [['role_name' => 'Admin']]]];
        $this->assertEquals($expectedResult, $this->roleMock->prepareDataSource($testData));
    }

    /**
     * unit test for role name when user role permission toogle is off
     *
     * @param array $dataSource
     * @return array
     */
    public function testPrepareDataSourcefalse()
    {
        $this->data['name'] = "Users | Edit";
        $this->setName('name', 'role_name');
        //~ $this->name = 'email12';
        $this->companyUserMock->expects($this->any())->method('toggleCustomerRolesAndPermissions')->willReturn(false);
        $testData = ['data' => ['items' => [['role_name' => 'Company Administrator']]]];
        $expectedResult = ['data' => ['items' => [['role_name' => 'Admin']]]];
        $this->assertNotEquals($expectedResult, $this->roleMock->prepareDataSource($testData));
    }
}