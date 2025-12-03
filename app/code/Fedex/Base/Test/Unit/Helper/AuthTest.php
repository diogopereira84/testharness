<?php

namespace Fedex\Base\Test\Unit\Helper;

use Fedex\Base\Helper\Auth;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\CookieManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;

class AuthTest extends TestCase
{
    protected Context|MockObject $contextMock;
    protected HttpContext|MockObject $httpContextMock;
    protected MockObject|Session $customerSessionMock;
    protected MockObject|CompanyManagementInterface $companyManagementMock;
    protected MockObject|CompanyInterface $companyMock;
    protected MockObject|CompanyRepositoryInterface $companyRepositoryMock;
    protected MockObject|Http $httpRequestMock;
    protected MockObject|SearchCriteriaBuilder $searchCriteriaBuilderMock;
    protected MockObject|CookieManagerInterface $cookieManagerMock;
    protected Auth $authHelper;
    protected ToggleConfig|MockObject $toggleConfigMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpRequestMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cookieManagerMock = $this->getMockBuilder(CookieManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authHelper = new Auth(
            $this->contextMock,
            $this->httpContextMock,
            $this->customerSessionMock,
            $this->companyManagementMock,
            $this->companyRepositoryMock,
            $this->httpRequestMock,
            $this->searchCriteriaBuilderMock,
            $this->cookieManagerMock,
            $this->toggleConfigMock
        );
    }

    public function testIsLoggedInReturnsTrueWhenCustomerIsLoggedIn()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $result = $this->authHelper->isLoggedIn();
        $this->assertTrue($result);
    }

    public function testIsLoggedInReturnsFalseWhenCustomerIsNotLoggedIn()
    {
        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);
        $result = $this->authHelper->isLoggedIn();
        $this->assertFalse($result);
    }

    public function testGetCompanyAuthenticationMethodNoCompany()
    {
        $this->testGetCompanyNoCompany();
        $result = $this->authHelper->getCompanyAuthenticationMethod();
        $this->assertEquals('none', $result);
    }

    public function testGetCompanyAuthenticationMethodFcl()
    {
        $this->testGetCompanyWithUrlParameter();
        $result = $this->authHelper->getCompanyAuthenticationMethod();
        $this->assertEquals('fcl', $result);
    }

    public function testGetCompanyAuthenticationMethodSso()
    {
        $this->testGetCompanyCustomerSession();
        $result = $this->authHelper->getCompanyAuthenticationMethod();
        $this->assertEquals('sso', $result);
    }

    public function testGetCompanyAuthenticationMethodSsoUrlCookie()
    {
        $this->testGetCompanyWithUrlCookie();
        $result = $this->authHelper->getCompanyAuthenticationMethod();
        $this->assertEquals('sso', $result);
    }

    public function testGetCompanyCustomerSession(): void
    {
        $this->customerSessionMock->method('isLoggedIn')
            ->willReturn(true);

        $this->customerSessionMock->expects($this->atLeastOnce())
            ->method('getCustomerId')
            ->willReturn(3);

        $this->companyMock->method('getId')
            ->willReturn(33);

        $this->companyMock->method('getData')
            ->with('storefront_login_method_option')
            ->willReturn('commercial_store_sso');

        $this->companyManagementMock->expects($this->atLeastOnce())
            ->method('getByCustomerId')
            ->willReturn($this->companyMock);

        $result = $this->authHelper->getCompany();
        $this->assertInstanceOf(CompanyInterface::class, $result);
    }

    public function testGetCompanyWithUrlParameter(): void
    {
        $urlExtension = 'target';
        $this->customerSessionMock
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->httpRequestMock
            ->method('getParam')
            ->with('url')
            ->willReturn($urlExtension);

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('company_url_extention', $urlExtension)
            ->willReturnSelf();

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $companyMock->method('getData')
            ->with('storefront_login_method_option')
            ->willReturn('commercial_store_wlgn');

        $companyMock->method('getId')->willReturn(6);
        $companyResultMock = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyResultMock->method('getItems')
            ->willReturn([$companyMock]);

        $this->companyRepositoryMock
            ->method('getList')
            ->willReturn($companyResultMock);

        $result = $this->authHelper->getCompany();
        $this->assertInstanceOf(CompanyInterface::class, $result);
    }

    public function testGetCompanyWithUrlCookie(): void
    {
        $urlExtension = 'target';
        $this->customerSessionMock
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->httpRequestMock
            ->method('getParam')
            ->with('url')
            ->willReturn(null);

        $this->cookieManagerMock->method('getCookie')
            ->with('url_extension')
            ->willReturn($urlExtension);

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('company_url_extention', $urlExtension)
            ->willReturnSelf();

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $companyMock->method('getData')
            ->with('storefront_login_method_option')
            ->willReturn('commercial_store_sso');

        $companyMock->method('getId')->willReturn(33);
        $companyResultMock = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyResultMock->method('getItems')
            ->willReturn([$companyMock]);

        $this->companyRepositoryMock
            ->method('getList')
            ->willReturn($companyResultMock);

        $result = $this->authHelper->getCompany();
        $this->assertInstanceOf(CompanyInterface::class, $result);
    }

    public function testGetCompanyNoCompany(): void
    {
        $this->customerSessionMock
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->httpRequestMock
            ->method('getParam')
            ->with('url')
            ->willReturn(null);

        $result = $this->authHelper->getCompany();
        $this->assertNull($result);
    }

}
