<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\CustomerData;

use Fedex\SDE\Helper\SdeHelper;
use Fedex\SSO\Api\Data\ConfigInterface as ModuleConfig;
use Fedex\SSO\CustomerData\SSOSection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Checkout\Model\Session as CheckoutSession;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\Session as CustomerSession;
use Fedex\Cart\Helper\Data as CartHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SSO\ViewModel\SsoConfiguration;

class SSOSectionTest extends TestCase
{
    protected $ssoSection;
    /**
     * @var ModuleConfig|MockObject
     */
    protected $moduleConfigMock;

    /**
     * @var SdeHelper|MockObject
     */
    protected $sdeHelperMock;

    /**
     * @var CheckoutSession|MockObject
     */
    protected $checkoutSession;
    
    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;
    
    /**
     * @var CompanyHelper|MockObject
     */
    protected $companyHelper;

    /**
     * @var CustomerRepository|MockObject
     */
    protected $customerRepository;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSession;

    /**
     * @var CartHelper|MockObject
     */
    protected $cartHelper;

    /**
     * @var SsoConfiguration|MockObject
     */
    protected $ssoConfiguration;

    /**
     * @var SelfReg|MockObject
     */
    protected $selfReg;

    /**
     * Setup mock objects
     */
    protected function setUp(): void
    {
        $this->moduleConfigMock = $this->createMock(ModuleConfig::class);

        $this->sdeHelperMock = $this->createMock(SdeHelper::class);

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods([
                'getQuote',
                'getData',
                'getRemoveFedexAccountNumber'
            ])
            ->disableOriginalConstructor()
            ->getMock();
            
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods([
                'getToggleConfigValue'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->companyHelper = $this->getMockBuilder(CompanyHelper::class)
            ->setMethods([
                'getFedexAccountNumber'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->customerRepository = $this->getMockBuilder(CustomerRepository::class)
            ->setMethods([
                'getById',
                'getExtensionAttributes',
                'getCompanyAttributes',
                'getCompanyId'
            ])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->setMethods([
                'getCustomerId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->cartHelper = $this->getMockBuilder(CartHelper::class)
            ->setMethods([
                'getDefaultFxoNumberForFCLUser',
                'decryptData'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->selfReg = $this->getMockBuilder(SelfReg::class)
            ->setMethods([
                'isSelfRegCompany'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->ssoConfiguration = $this->getMockBuilder(SsoConfiguration::class)
            ->setMethods([
                'isFclCustomer'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ssoSection = (new ObjectManager($this))->getObject(
            SSOSection::class,
            [
                'moduleConfig' => $this->moduleConfigMock,
                'sdeHelper' => $this->sdeHelperMock,
                'checkoutSession' => $this->checkoutSession,
                'toggleConfig' => $this->toggleConfig,
                'companyHelper' => $this->companyHelper,
                'customerRepository' => $this->customerRepository,
                'customerSession' => $this->customerSession,
                'cartHelper' => $this->cartHelper,
                'selfReg' => $this->selfReg,
                'ssoConfiguration' => $this->ssoConfiguration
            ]
        );
    }

    /**
     * Test getSectionData with toggle
     */
    public function testGetSectionDataWithToggle()
    {
        $response = [
            'login_page_url' => 'some-url',
            'login_page_query_parameter' => 'some-query',
            'sensitive_data_workflow' => true,
            'fedex_account_number' => '12345678',
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyId')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getData')
            ->willReturn('12345678');
        
        $this->companyHelper->expects($this->any())
            ->method('getFedexAccountNumber')
            ->willReturn('12345678');
        
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(1);

        $this->moduleConfigMock->expects($this->any())
            ->method('getLoginPageURL')
            ->willReturn('some-url');

        $this->moduleConfigMock->expects($this->any())
            ->method('getQueryParameter')
            ->willReturn('some-query');

        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        
        $this->cartHelper->expects($this->any())
            ->method('decryptData')
            ->willReturn(12345678);

        $this->assertEquals($response, $this->ssoSection->getSectionData());
    }

    /**
     * Test getSectionData with toggle
     */
    public function testGetSectionDataWithToggleOne()
    {
        $response = [
            'login_page_url' => 'some-url',
            'login_page_query_parameter' => 'some-query',
            'sensitive_data_workflow' => true,
            'fedex_account_number' => '12345678',
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyId')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();

        $this->selfReg->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(1);

        $this->moduleConfigMock->expects($this->any())
            ->method('getLoginPageURL')
            ->willReturn('some-url');

        $this->moduleConfigMock->expects($this->any())
            ->method('getQueryParameter')
            ->willReturn('some-query');

        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        
        $this->cartHelper->expects($this->any())
            ->method('decryptData')
            ->willReturn(12345678);

        $this->assertNotEquals($response, $this->ssoSection->getSectionData());
    }

    /**
     * Test getSectionData with fcl and remove account
     */
    public function testGetSectionDataWithToggleOneWithFCLAndRemoveAcc()
    {
        $response = [
            'login_page_url' => 'some-url',
            'login_page_query_parameter' => 'some-query',
            'sensitive_data_workflow' => true,
            'fedex_account_number' => '12345678',
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyId')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();
        
        $this->ssoConfiguration->expects($this->any())
            ->method('isFclCustomer')
            ->willReturn(1);
        
        $this->checkoutSession->expects($this->any())
            ->method('getRemoveFedexAccountNumber')
            ->willReturn(0);
        
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(1);

        $this->moduleConfigMock->expects($this->any())
            ->method('getLoginPageURL')
            ->willReturn('some-url');

        $this->moduleConfigMock->expects($this->any())
            ->method('getQueryParameter')
            ->willReturn('some-query');

        $this->sdeHelperMock->expects($this->any())
            ->method('getIsSdeStore')
            ->willReturn(true);
        
        $this->cartHelper->expects($this->any())
            ->method('decryptData')
            ->willReturn(12345678);

        $this->assertNotEquals($response, $this->ssoSection->getSectionData());
    }

    /**
     * Test getSectionData with FCL login
     */
    public function testGetSectionDataWithLogin()
    {
        $response = [
            'login_page_url' => 'some-url',
            'login_page_query_parameter' => 'some-query',
            'sensitive_data_workflow' => true,
            'fedex_account_number' => '12345678',
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->customerSession->expects($this->any())
            ->method('getCustomerId')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getById')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyAttributes')
            ->willReturnSelf();
        
        $this->customerRepository->expects($this->any())
            ->method('getCompanyId')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();
        
        $this->checkoutSession->expects($this->any())
            ->method('getData')
            ->willReturn('12345678');
        
        $this->selfReg->expects($this->any())
            ->method('isSelfRegCompany')
            ->willReturn(0);

        $this->moduleConfigMock->expects($this->any())
            ->method('getLoginPageURL')
            ->willReturn('some-url');

        $this->moduleConfigMock->expects($this->any())
            ->method('getQueryParameter')
            ->willReturn('some-query');

        $this->assertNotEquals($response, $this->ssoSection->getSectionData());
    }

    /**
     * Test getSectionData without login
     */
    public function testGetSectionDataWithoutLogin()
    {
        $response = [
            'login_page_url' => 'some-url',
            'login_page_query_parameter' => 'some-query',
            'sensitive_data_workflow' => true,
            'fedex_account_number' => '12345678',
        ];

        $this->toggleConfig->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        
        $this->checkoutSession->expects($this->any())
            ->method('getQuote')
            ->willReturnSelf();

        $this->assertNotEquals($response, $this->ssoSection->getSectionData());
    }
}
