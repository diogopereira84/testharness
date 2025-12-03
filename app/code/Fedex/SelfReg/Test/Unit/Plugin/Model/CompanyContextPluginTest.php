<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Test\Unit\Plugin\Model;

use Fedex\SelfReg\Plugin\Model\CompanyContextPlugin;
use Magento\Company\Model\CompanyContext;
use Fedex\Delivery\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyContextPluginTest extends TestCase
{
    /**
     * @var CompanyContextPlugin
     */
    private $companyContextPlugin;

    /**
     * @var MockObject|Data
     */
    private $deliveryHelperMock;

    protected function setUp(): void
    {
        $this->deliveryHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyContextPlugin = new CompanyContextPlugin(
            $this->deliveryHelperMock
        );
    }

    public function testAfterIsResourceAllowedWhenRolesAndPermissionsEnabledAndPermissionGranted()
    {
        $subject = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = false;
        $resource = 'Magento_Company::user';

        $this->deliveryHelperMock->expects($this->once())
            ->method('getToggleConfigurationValue')
            ->with('change_customer_roles_and_permissions')
            ->willReturn(true);

        $this->deliveryHelperMock->expects($this->once())
            ->method('checkPermission')
            ->with('manage_users')
            ->willReturn(true);

        $actualResult = $this->companyContextPlugin->afterIsResourceAllowed($subject, $result, $resource);

        $this->assertTrue($actualResult);
    }

    public function testAfterIsResourceAllowedWhenRolesAndPermissionsEnabledAndPermissionDenied()
    {
        $subject = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = false;
        $resource = 'Magento_Company::user';

        $this->deliveryHelperMock->expects($this->once())
            ->method('getToggleConfigurationValue')
            ->with('change_customer_roles_and_permissions')
            ->willReturn(true);

        $this->deliveryHelperMock->expects($this->once())
            ->method('checkPermission')
            ->with('manage_users')
            ->willReturn(false);

        $actualResult = $this->companyContextPlugin->afterIsResourceAllowed($subject, $result, $resource);

        $this->assertFalse($actualResult);
    }

    public function testAfterIsResourceAllowedWhenRolesAndPermissionsDisabled()
    {
        $subject = $this->getMockBuilder(CompanyContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = false;
        $resource = 'Magento_Company::user';

        $this->deliveryHelperMock->expects($this->once())
            ->method('getToggleConfigurationValue')
            ->with('change_customer_roles_and_permissions')
            ->willReturn(false);

        $actualResult = $this->companyContextPlugin->afterIsResourceAllowed($subject, $result, $resource);

        $this->assertFalse($actualResult);
    }
}