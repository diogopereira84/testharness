<?php

namespace Fedex\Base\Test\Unit\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\Context;
use Fedex\Base\Helper\Sde;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Company\Api\Data\CompanySearchResultsInterface;

class SdeTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $toggleConfigMock;
    protected Sde $sdeHelper;
    protected MockObject|Session $customerSessionMock;
    protected CompanyManagementInterface|MockObject $companyManagementMock;
    protected CompanyRepositoryInterface|MockObject $companyRepositoryMock;
    protected SearchCriteriaBuilder|MockObject $searchCriteriaBuilderMock;
    protected HttpRequest|MockObject $httpRequestMock;
    protected ToggleConfig|MockObject $toggleConfig;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyManagementMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpRequestMock = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sdeHelper = new Sde(
            $this->contextMock,
            $this->customerSessionMock,
            $this->companyRepositoryMock,
            $this->companyManagementMock,
            $this->httpRequestMock,
            $this->searchCriteriaBuilderMock,
            $this->toggleConfigMock
        );
    }

    public function testIsSensitiveDataFlowWithCustomerSession()
    {
        $this->testGetCompanyWithCustomerSession();
        $this->assertTrue($this->sdeHelper->isSensitiveDataFlow());
    }

    public function testIsSensitiveDataFlowWithUrlParameter()
    {
        $this->testGetCompanyWithUrlParameter();
        $this->assertTrue($this->sdeHelper->isSensitiveDataFlow());
    }

    public function testIsSensitiveDataFlowWithNoCompanyLocated()
    {
        $this->testGetCompanyNoResults();
        $this->assertFalse($this->sdeHelper->isSensitiveDataFlow());
    }

    public function testGetCompanyWithCustomerSession()
    {
        $this->customerSessionMock
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock
            ->method('getCustomerId')
            ->willReturn(2);
        $companyMock = $this->getMockBuilder(CompanyInterface::class)
            ->addMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyMock->method('getData')->with(Sde::IS_SENSITIVE_DATA_ENABLED)->willReturn(true);
        $this->companyManagementMock->method('getByCustomerId')->willReturn($companyMock);
        $result = $this->sdeHelper->getCompany();
        $this->assertInstanceOf(CompanyInterface::class, $result);
    }

    public function testGetCompanyWithUrlParameter()
    {
        $urlExtension = 'srs1234';
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
            ->with(Sde::IS_SENSITIVE_DATA_ENABLED)
            ->willReturn(true);

        $companyResultMock = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyResultMock->method('getItems')
            ->willReturn([$companyMock]);

        $this->companyRepositoryMock
            ->method('getList')
            ->willReturn($companyResultMock);

        $result = $this->sdeHelper->getCompany();
        $this->assertInstanceOf(CompanyInterface::class, $result);
    }

    public function testGetCompanyNoResults()
    {
        $this->customerSessionMock
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->httpRequestMock
            ->method('getParam')
            ->with('url')
            ->willReturn('');

        $this->searchCriteriaBuilderMock
            ->method('addFilter')
            ->with('company_url_extention', '')
            ->willReturnSelf();

        $searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->searchCriteriaBuilderMock
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $companyResultMock = $this->getMockBuilder(CompanySearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyResultMock->method('getItems')
            ->willReturn([]);

        $this->companyRepositoryMock
            ->method('getList')
            ->willReturn($companyResultMock);

        $result = $this->sdeHelper->getCompany();
        $this->assertNull($result);
    }

}
