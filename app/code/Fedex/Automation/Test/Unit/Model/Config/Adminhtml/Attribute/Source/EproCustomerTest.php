<?php
/**
 * @category  Fedex
 * @package   Fedex_Automation
 * @author    Martin Arrua <martin.arrua.osv@fedex.com>
 * @copyright 2025 Fedex
 */
declare(strict_types=1);

namespace Fedex\Automation\Test\Unit\Model\Config\Adminhtml\Attribute\Source;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Company\Model\ResourceModel\Customer as CompanyCustomer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\Automation\Model\Config\Adminhtml\Attribute\Source\EproCustomer;
use Psr\Log\LoggerInterface;

class EproCustomerTest extends TestCase
{
    private MockObject|EproCustomer $eproCustomer;
    private CompanyRepositoryInterface|MockObject $companyRepositoryMock;
    private CustomerRepositoryInterface|MockObject $customerRepositoryMock;
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilderMock;
    private FilterBuilder|MockObject $filterBuilderMock;
    private CompanyCustomer|MockObject $companyCustomerMock;
    private LoggerInterface|MockObject $loggerMock;

    protected function setUp(): void
    {
        $this->companyRepositoryMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilderMock = $this->createMock(FilterBuilder::class);
        $this->companyCustomerMock = $this->createMock(CompanyCustomer::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->eproCustomer = new EproCustomer(
            $this->companyRepositoryMock,
            $this->customerRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->filterBuilderMock,
            $this->companyCustomerMock,
            $this->loggerMock
        );
    }

    public function testToOptionArrayWithCustomers(): void
    {

    }

    public function testToOptionArrayWithNoCustomers(): void
    {

    }
}
