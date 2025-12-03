<?php

declare(strict_types=1);

namespace Fedex\SelfReg\Test\Unit\Model;

use Fedex\SelfReg\Model\FindGroupModel;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\GroupInterface;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Ui\Component\Listing\Column\CompanyUsersActions;

class FindGroupModelTest extends TestCase
{
    private $customerRepository;
    private $groupRepository;
    private $filterBuilder;
    private $searchCriteriaBuilder;
    private $findGroupModel;
    private $companyUsersActions;

    protected function setUp(): void
    {
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->companyUsersActions = $this->createMock(CompanyUsersActions::class);
        $this->filterBuilder->method('setField')->willReturnSelf();
        $this->filterBuilder->method('setValue')->willReturnSelf();
        $this->filterBuilder->method('setConditionType')->willReturnSelf();
        $this->filterBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaBuilder->method('addFilters')->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $this->findGroupModel = new FindGroupModel(
            $this->customerRepository,
            $this->groupRepository,
            $this->filterBuilder,
            $this->searchCriteriaBuilder,
            $this->companyUsersActions
        );
    }

    public function testGetAllCustomersGroupName()
    {
        $selectedUserIds = '1,2,3';
        $expectedResult = ['Group1', 'Group2'];

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getGroupId')->willReturn(1);

        $group1 = $this->createMock(GroupInterface::class);
        $group1->method('getCode')->willReturn('Group1');

        $group2 = $this->createMock(GroupInterface::class);
        $group2->method('getCode')->willReturn('Group2');

        $customerSearchResult = $this->createMock(\Magento\Customer\Api\Data\CustomerSearchResultsInterface::class);
        $customerSearchResult->method('getItems')->willReturn([$customer]);

        $groupSearchResult = $this->createMock(\Magento\Customer\Api\Data\GroupSearchResultsInterface::class);
        $groupSearchResult->method('getItems')->willReturn([$group1, $group2]);

        $this->customerRepository->expects($this->once())
            ->method('getList')
            ->willReturn($customerSearchResult);

        $this->groupRepository->expects($this->once())
            ->method('getList')
            ->willReturn($groupSearchResult);

        $this->assertEquals($expectedResult, $this->findGroupModel->getAllCustomersGroupName($selectedUserIds));
    }

    public function testGetAllCustomerGroupCodes()
    {
        $selectedUserIds = [1, 2, 3];
        $expectedResult = ['Group1', 'Group2'];

        $group1 = $this->createMock(GroupInterface::class);
        $group1->method('getCode')->willReturn('Group1');

        $group2 = $this->createMock(GroupInterface::class);
        $group2->method('getCode')->willReturn('Group2');

        $groupSearchResult = $this->createMock(\Magento\Customer\Api\Data\GroupSearchResultsInterface::class);
        $groupSearchResult->method('getItems')->willReturn([$group1, $group2]);

        $this->groupRepository->expects($this->once())
            ->method('getList')
            ->willReturn($groupSearchResult);

        $this->assertEquals($expectedResult, $this->findGroupModel->getAllCustomerGroupCodes($selectedUserIds));
    }
}