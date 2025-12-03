<?php

declare(strict_types=1);

namespace Fedex\CustomerGroup\Test\Unit\Controller\Adminhtml\Options;

use Fedex\CustomerGroup\Controller\Adminhtml\Options\BulkSavePermissions;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Magento\Backend\App\Action\Context;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\Company\Model\AdditionalDataFactory;
use Magento\Company\Api\CompanyRepositoryInterface;

class BulkSavePermissionsTest extends TestCase
{
    /** @var Context|MockObject */
    private $contextMock;
    /** @var JsonFactory|MockObject */
    private $resultJsonFactoryMock;
    /** @var Json|MockObject */
    private $resultJsonMock;
    /** @var Http|MockObject */
    private $requestMock;
    /** @var LoggerInterface|MockObject */
    private $loggerMock;
    /** @var CustomerRepositoryInterface|MockObject */
    private $customerRepositoryMock;
    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilderMock;
    /** @var CustomerFactory|MockObject */
    private $customerFactoryMock;
    /** @var CompanyManagementInterface|MockObject */
    private $companyManagementMock;
    /** @var AdminConfigHelper|MockObject */
    private $adminConfigHelperMock;
    /** @var EnhanceUserRolesFactory|MockObject */
    private $enhanceUserRolesFactoryMock;
    /** @var ResourceConnection|MockObject */
    private $resourceConnectionMock;
    /** @var AdapterInterface|MockObject */
    private $dbConnectionMock;
    /** @var BulkSavePermissions */
    private $controller;
    /** @var CompanyRepositoryInterface|MockObject */
    private $companyRepositoryInterface;
    /** @var AdditionalDataFactory|MockObject */
    private $additionalDataFactory;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->resultJsonMock = $this->createMock(Json::class);
        $this->requestMock = $this->createMock(Http::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->customerFactoryMock = $this->createMock(CustomerFactory::class);
        $this->companyManagementMock = $this->createMock(CompanyManagementInterface::class);
        $this->adminConfigHelperMock = $this->createMock(AdminConfigHelper::class);
        $this->enhanceUserRolesFactoryMock = $this->createMock(EnhanceUserRolesFactory::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->dbConnectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->companyRepositoryInterface = $this->createMock(CompanyRepositoryInterface::class);
        $this->additionalDataFactory = $this->createMock(AdditionalDataFactory::class);
        
        $this->controller = new BulkSavePermissions(
            $this->contextMock,
            $this->resultJsonFactoryMock,
            $this->requestMock,
            $this->loggerMock,
            $this->customerRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->customerFactoryMock,
            $this->companyManagementMock,
            $this->adminConfigHelperMock,
            $this->enhanceUserRolesFactoryMock,
            $this->resourceConnectionMock,
            $this->companyRepositoryInterface,
            $this->additionalDataFactory
        );
    }

    public function testExecuteSuccess()
    {
        $customerIds = [1, 2];
        $requestData = [
            'selectedIds' => [1, 2],
            'selectedPermissions' => ['manage_catalog', 'review_orders']
        ];
        $companyDataMock = $this->getMockBuilder(\Magento\Company\Api\Data\CompanyInterface::class)
            ->addMethods(['getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $companyDataMock->method('getId')->willReturn(10);
        $companyDataMock->method('getAllowSharedCatalog')->willReturn(true);

        $this->resultJsonFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultJsonMock);

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn($requestData);

        $this->companyManagementMock->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($companyDataMock);

        $this->adminConfigHelperMock->expects($this->any())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);

        $this->resourceConnectionMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->dbConnectionMock);

        $this->dbConnectionMock->expects($this->any())
            ->method('insertOnDuplicate')
            ->with(
                BulkSavePermissions::ENHANCED_USER_ROLES_TABLE_NAME,
                $this->callback(function ($data) {
                    return is_array($data) && count($data) > 0;
                })
            );

        $this->resultJsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $searchCriteriaMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaInterface::class);
        $customerMock1 = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $customerMock2 = $this->createMock(\Magento\Customer\Api\Data\CustomerInterface::class);
        $searchResultsMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);

        $this->searchCriteriaBuilderMock
            ->expects($this->any())
            ->method('addFilter')
            ->with('entity_id', $customerIds, 'in')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteriaMock);

        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaMock)
            ->willReturn($searchResultsMock);

        $searchResultsMock
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$customerMock1, $customerMock2]);

        $result = $this->controller->execute();
        $this->assertSame($this->resultJsonMock, $result);
    }
}
