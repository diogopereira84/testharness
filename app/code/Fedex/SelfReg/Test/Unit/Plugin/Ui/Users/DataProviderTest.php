<?php

/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Plugin\Ui\Users;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Fedex\SelfReg\Plugin\Ui\Users\DataProvider;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Company\Ui\DataProvider\Users\DataProvider as Subject;
use Magento\Company\Model\ResourceModel\Users\Grid\Collection;
use Fedex\Login\Helper\Login;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class DataProviderTest extends TestCase
{
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $request;
    protected $selfReg;
    protected $subject;
    protected $result;
    /**
     * @var (\Magento\Eav\Model\ResourceModel\Entity\Attribute & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $attribute;
    protected $login;
    protected $deliveryHelperMock;
    protected $dataProvider;
    /**
     * @inheritDoc
     * 
     */
    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();
        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['toggleSelfRegLoginEnable', 'isSelfRegCustomer','isSelfRegCustomerAdmin', 'toggleUserRolePermissionEnable','companyAdminSuperUserId', 'getCompanyUserPermission'])
            ->getMock();
        $this->subject = $this->getMockBuilder(Subject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->result = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelect', 'order', 'addFieldToFilter','where', 'join','group','distinct'])
            ->getMock();
        $this->attribute = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdByCode'])
            ->getMock();
        $this->login = $this->getMockBuilder(Login::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCompanyId'])
            ->getMock();
        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkPermission'])
            ->getMock();
        $objectManagerHelper = new ObjectManager($this);
        $this->dataProvider = $objectManagerHelper->getObject(
            DataProvider::class,
            [
                'toggleConfig' => $this->toggleConfig,
                'request' => $this->request,
                'selfReg' => $this->selfReg,
                'eavAttribute' => $this->attribute,
                'login' => $this->login,
                'deliveryHelper'=> $this->deliveryHelperMock
            ]
        );
    }

    /**
     * @test testAfterGetSearchResult
     */
    public function testAfterGetSearchResult()
    {
        $paramData = [];
        $paramData['sorting']['field'] = "email";
        $paramData['sorting']['direction'] = "desc";
        $this->selfReg->expects($this->any())
            ->method('toggleSelfRegLoginEnable')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('toggleUserRolePermissionEnable')
            ->willReturn(true);
        $this->testFilterUser(['filter' => ['status' => 1]]);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn($paramData);
        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->result->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->deliveryHelperMock->expects($this->any())
            ->method('checkPermission')
            ->willReturn(true);
        $this->assertEquals($this->result,$this->dataProvider->afterGetSearchResult($this->subject, $this->result));
    }

    /**
     * @test testAfterGetSearchResultWithoutParams
     */
    public function testAfterGetSearchResultWithoutParams()
    {
        $paramData = [];
        $this->selfReg->expects($this->any())
            ->method('toggleSelfRegLoginEnable')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn($paramData);
        $this->selfReg->expects($this->any())
            ->method('toggleUserRolePermissionEnable')
            ->willReturn(true);
        $this->testFilterUser();
        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
            
        $this->result->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->afterGetSearchResult($this->subject, $this->result));
    }

    /**
     * @test testAfterGetSearchResultWithFilter
     */
    public function testAfterGetSearchResultWithFilter()
    {
        $paramData = [];
        $paramData['filter'] = "1,2";
        $this->selfReg->expects($this->any())
            ->method('toggleSelfRegLoginEnable')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn($paramData);
        $this->selfReg->expects($this->any())
            ->method('toggleUserRolePermissionEnable')
            ->willReturn(false);
        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
            
        $this->result->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->afterGetSearchResult($this->subject, $this->result));
    }

    /**
     * @test testAfterGetSearchResultWithSearch
     */
    public function testAfterGetSearchResultWithSearch()
    {
        $paramData = [];
        $paramData['search'] = "test";
        $this->selfReg->expects($this->any())
            ->method('toggleSelfRegLoginEnable')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(true);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn($paramData);

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
            
        $this->result->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->afterGetSearchResult($this->subject, $this->result));
    }

    /**
     * @test testAfterGetSearchResultForNonSelfReg
     */
    public function testAfterGetSearchResultForNonSelfReg()
    {
        $paramData = [];
        $this->selfReg->expects($this->any())
            ->method('toggleSelfRegLoginEnable')
            ->willReturn(false);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomer')
            ->willReturn(false);
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCustomerAdmin')
            ->willReturn(false);
        $this->request->expects($this->any())
            ->method('getParams')
            ->willReturn($paramData);

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
            
        $this->result->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->afterGetSearchResult($this->subject, $this->result));
    }

    /**
     * @test testFilterUser
     */
    public function testFilterUser()
    {
        $param = ['filter' => [
            'status' => 1,
            'shared_orders' => 1,
            'manage_users' => 1,
            'shared_credit_cards' => 1,
            'manage_catalog' => 1
        ]];

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->result->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(8);
        $this->selfReg->expects($this->any())
            ->method('getCompanyUserPermission')
            ->willReturn(['shared_catalog']);
        $this->result->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->filterUser($this->result, $param));
    }

    /**
     * @test testFilterUser
     */
    public function testFilterUserWithoutPermission()
    {
        $param = ['filter' => [
            'status' => 1,
            'shared_orders' => 1,
            'manage_users' => 1,
            'shared_credit_cards' => 1,
            'manage_catalog' => 1
        ]];

        $this->result->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->result->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $this->login->expects($this->any())
            ->method('getCompanyId')
            ->willReturn(8);
        $this->selfReg->expects($this->any())
            ->method('getCompanyUserPermission')
            ->willReturn([]);
        $this->result->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->assertEquals($this->result,$this->dataProvider->filterUser($this->result, $param));
    }
}
