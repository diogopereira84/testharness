<?php
declare(strict_types=1);

namespace Fedex\Login\Test\Unit\Plugin;

use Fedex\Login\Plugin\LoginAsCustomerAfterPlugin;
use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\LoginAsCustomerFrontendUi\Controller\Login\Index as LoginAsCustomerIndex;
use Fedex\Login\Helper\Login;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Model\Company;

class LoginAsCustomerAfterPluginTest extends TestCase
{
    private $customerSession;
    private $login;
    private $toggleConfig;
    private $plugin;
    private $companyManagementInterfaceMock;
    private $companyModelMock;

    protected function setUp(): void
    {
        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
        ->setMethods(['isLoggedIn', 'getCustomer', 'setCustomerCompany','setCommunicationUrl']) // Specify methods to mock
        ->disableOriginalConstructor()
        ->getMock();
        $this->login = $this->createMock(Login::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->companyManagementInterfaceMock = $this->getMockBuilder(CompanyManagementInterface::class)
        ->setMethods(["getByCustomerId"])->disableOriginalConstructor()->getMockForAbstractClass();
        $this->companyModelMock = $this->getMockBuilder(Company::class)
        ->setMethods(["getData"])->disableOriginalConstructor()->getMock();
        $this->plugin = new LoginAsCustomerAfterPlugin(
            $this->customerSession,
            $this->login,
            $this->toggleConfig,
            $this->companyManagementInterfaceMock

        );
    }

    /**
     * Test execute when toggle enable and login
     *
     * @return void
     */
    public function testAfterExecuteWhenImpersonatorIsEnabledAndLoggedIn()
    {
        $customerId = 1;
        $companyId = 100;

        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomer')->willReturn($this->createConfiguredMock(\Magento\Customer\Model\Data\Customer::class, [
            'getId' => $customerId,
        ]));
        
        $this->login->method('getCompanyId')->with($customerId)->willReturn($companyId);
        $this->customerSession->expects($this->once())->method('setCustomerCompany')->with($companyId);
        $this->companyManagementInterfaceMock->expects($this->once())->method('getByCustomerId')
        ->willReturn($this->companyModelMock);
        $resultMock = $this->createMock(ResultInterface::class);
        $subjectMock = $this->createMock(LoginAsCustomerIndex::class);

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
    }

    /**
     * Test execute when toggle disable and login
     *
     * @return void
     */
    public function testAfterExecuteWhenImpersonatorIsDisabled()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);
        $this->customerSession->method('isLoggedIn')->willReturn(true);

        $resultMock = $this->createMock(ResultInterface::class);
        $subjectMock = $this->createMock(LoginAsCustomerIndex::class);

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
        // Ensure setCustomerCompany is not called
        $this->customerSession->expects($this->never())->method('setCustomerCompany');
    }

    /**
     * Test execute when toggle enable and user not login
     *
     * @return void
     */
    public function testAfterExecuteWhenNotLoggedIn()
    {
        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);
        $this->customerSession->method('isLoggedIn')->willReturn(false);

        $resultMock = $this->createMock(ResultInterface::class);
        $subjectMock = $this->createMock(LoginAsCustomerIndex::class);

        $result = $this->plugin->afterExecute($subjectMock, $resultMock);

        $this->assertSame($resultMock, $result);
        // Ensure setCustomerCompany is not called
        $this->customerSession->expects($this->never())->method('setCustomerCompany');
    }
}
