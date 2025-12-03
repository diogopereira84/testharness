<?php

namespace Fedex\Company\Test\Unit\Model\Config\Source;

use Fedex\Company\Model\Config\Source\LoginOptions;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class LoginOptionsTest extends TestCase
{
    /**
     * @var $objectManager
     */
    protected $objectManager;

    /**
     * @var ToggleConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $toggleConfig;

    /**
     * @var LoginOptions
     */
    protected $loginOptions;

    protected function setUp(): void
    {
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->onlyMethods(['getToggleConfig', 'getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);

        $this->loginOptions = $objectManager->getObject(
            LoginOptions::class,
            [
                'toggleConfig' => $this->toggleConfig
            ]
        );
    }

    public function testToOptionArrayWithAddNewCompanyDisabledAndSsoWithFclDisabled()
    {
        $this->toggleConfig->method('getToggleConfig')
            ->with(LoginOptions::ADD_NEW_COMPANY_REQUIRED_FIELDS)
            ->willReturn(false);

        $loginOptions = [
            ['value' => '', 'label' => __('Select Login Options')],
            ['value' => 'commercial_store_wlgn', 'label' => __('FCL')],
            ['value' => 'commercial_store_sso', 'label' => __('SSO')],
            ['value' => 'commercial_store_epro', 'label' => __('EPro Punchout')],
        ];

        $this->assertEquals($loginOptions, $this->loginOptions->toOptionArray());
    }


    public function testToOptionArrayWithAddNewCompanyEnabledAndSsoWithFclDisabled()
    {
        $this->toggleConfig->method('getToggleConfig')
            ->with(LoginOptions::ADD_NEW_COMPANY_REQUIRED_FIELDS)
            ->willReturn(true);

        $this->toggleConfig->method('getToggleConfigValue')
            ->with('xmen_enable_sso_group_authentication_method')
            ->willReturn(true);

        $expectedResult = [
            ['value' => 'commercial_store_wlgn', 'label' => __('FCL')],
            ['value' => 'commercial_store_sso', 'label' => __('SSO')],
            ['value' => 'commercial_store_epro', 'label' => __('EPro Punchout')],
            ['value' => 'commercial_store_sso_with_fcl', 'label' => __('SSO with FCL User')],
        ];

        $result = $this->loginOptions->toOptionArray();
        $this->assertEquals($expectedResult, $result);
    }



}
