<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Fedex\Commercial\Test\Unit\Helper;

use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;

/**
 * Test class for SdeSsoConfiguration
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CommercialHelperTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $sdeHelper;
    protected $toggleConfig;
    protected $delivaryHelper;
    protected $selfreghelper;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $commercialHelper;
    /**
     * Test setUp
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = $this->getMockBuilder(SdeHelper::class)
            ->setMethods(['getIsSdeStore'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->delivaryHelper = $this->getMockBuilder(\Fedex\Delivery\Helper\Data::class)
            ->setMethods([
                'isCommercialCustomer',
                'getAssignedCompany',
                'isEproCustomer',
                'getProductAttributeName',
                'getProductCustomAttributeValue',
                'getOnDemandCompInfo',
                'isSelfRegCustomerAdminUser',
                'isCompanyAdminUser'
            ])->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->selfreghelper = $this->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany','isSelfRegCustomer','isSelfRegCustomerAdmin'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->commercialHelper = $this->objectManager->getObject(
            commercialHelper::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfig,
                'sdeHelper' => $this->sdeHelper,
                'delivaryHelper' => $this->delivaryHelper,
                'selfRegHelper' => $this->selfreghelper
            ]
        );
    }

    /**
     * Test case for isRolePermissionToggleEnable
     */
    public function testIsRolePermissionToggleEnable()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')
        ->with('change_customer_roles_and_permissions')->willReturn(true);
        $this->assertTrue($this->commercialHelper->isRolePermissionToggleEnable());
    }
    /**
     *  test case for commercialHeaderAndFooterEnable
     */
    public function testCommercialHeaderAndFooterEnable()
    {
        $this->delivaryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        /** B-1857860 */
        $this->commercialHelper->commercialHeaderAndFooterEnable();
        /** B-1857860 */
        $this->assertTrue($this->commercialHelper->commercialHeaderAndFooterEnable());
    }

    /**
     *  test case for commercialHeaderAndFooterEnable
     */
    public function testCommercialHeaderAndFooterEnableTrue()
    {
        $this->delivaryHelper->expects($this->any())->method('isEproCustomer')->willReturn(false);
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(false);
        /** B-1857860 */
        $this->commercialHelper->commercialHeaderAndFooterEnable();
        $this->assertTrue($this->commercialHelper->commercialHeaderAndFooterEnable());
    }

    public function testCommercialHeaderAndFooterEnableSelfReg()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(true);
        $this->assertTrue($this->commercialHelper->commercialHeaderAndFooterEnable());
    }

    /**
     *  test case for isGlobalCommercialCustomer
     */
    public function testIsGlobalCommercialCustomer()
    {
        $this->testCommercialHeaderAndFooterEnableTrue();
        $this->delivaryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->assertTrue($this->commercialHelper->isGlobalCommercialCustomer());
    }

    /**
     *  test case for isCommercialReorderEnable
     */
    public function testIsCommercialReorderEnable()
    {
        $this->delivaryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->assertTrue($this->commercialHelper->isCommercialReorderEnable());
    }

    /**
     *  test case for isCommercialReorderEnable
     */
    public function testIsCommercialReorderEnableForTrue()
    {
        $this->delivaryHelper->expects($this->any())->method('isEproCustomer')->willReturn(true);
        $this->sdeHelper->expects($this->any())->method('getIsSdeStore')->willReturn(true);
        $this->assertFalse($this->commercialHelper->isCommercialReorderEnable());
    }
    /**
     *  test case for isSelfRegAdminUpdates
     */
    public function testIsSelfRegAdminUpdates()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(true);
        $this->commercialHelper->isSelfRegAdminUpdates();
        $this->assertTrue($this->commercialHelper->isSelfRegAdminUpdates());
    }
    public function testIsSelfRegAdminUpdatesForTrue()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomerAdmin')->willReturn(false);
        $this->commercialHelper->isSelfRegAdminUpdates();
        $this->assertFalse($this->commercialHelper->isSelfRegAdminUpdates());
    }

    public function testGetSelfRegAdminUser()
    {
        $this->delivaryHelper->expects($this->any())
            ->method('isSelfRegCustomerAdminUser')
            ->willReturn(true);
        $this->assertEquals(true, $this->commercialHelper->getSelfRegAdminUser());
    }

     /**
     *  test case for isSelfRegAdminUpdates
     */
    public function testGetCompanyInfo()
    {
        $companyInfo = [
            'company_id' => 1,
            'login_method' => 'commercial_store_wlgn',
            'is_sensitive_data_enabled' => 0,
            'logoutUrl' => 'URL',
            'company_url_extension' => 'test'
        ];
        $this->delivaryHelper->expects($this->any())->method('getOnDemandCompInfo')->willReturn($companyInfo);
        $this->assertEquals($companyInfo, $this->commercialHelper->getCompanyInfo());
    }

    /**
     *  test case for isSelfRegAdminUpdates
     */
    public function testGetCompanyAdminUser()
    {
        $this->delivaryHelper->expects($this->any())
            ->method('isCompanyAdminUser')
            ->willReturn(true);
        $this->assertEquals(true, $this->commercialHelper->getCompanyAdminUser());
    }

    /**
     * Test case for isPersonalAddressBookToggleEnable
     */
    public function testIsPersonalAddressBookToggleEnable()
    {
        $this->toggleConfig->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertTrue($this->commercialHelper->isPersonalAddressBookToggleEnable());
    }
}
