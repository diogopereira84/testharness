<?php
/**
 * @category  Fedex
 * @package   Fedex_SharedCatalogCustomization
 * @copyright Copyright (c) 2024 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\Source;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\SharedCatalogCustomization\Ui\Component\Form\Field\AdvancedSearchCustomerGroup;
use Fedex\SharedCatalogCustomization\Model\Source\Group;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    /**
     * @var GroupRepositoryInterface|MockObject
     */
    private $groupRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder|MockObject
     */
    private $sortOrderBuilder;

    /**
     * @var Group
     */
    private $customerGroupSource;

    /**
     * @var RequestInterface
     */
    private $requestInterface;

    /**
     * @var ConfigInterface
     */
    private $configInterface;

    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->groupRepository = $this->createMock(
            GroupRepositoryInterface::class
        );

        $this->sortOrderBuilder = $this->createMock(
            SortOrderBuilder::class
        );

        $this->searchCriteriaBuilder = $this->createMock(
            SearchCriteriaBuilder::class
        );

        $this->requestInterface = $this->createMock(
            RequestInterface::class
        );

        $this->configInterface = $this->createMock(
            ConfigInterface::class
        );

        $this->customerGroupSource = new Group(
            $this->groupRepository,
            $this->sortOrderBuilder,
            $this->searchCriteriaBuilder,
            $this->requestInterface,
            $this->configInterface
        );
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArrayEditCompany()
    {
        $groupId = 1;
        $groupName = 'Group Name';
        $CompanyId = 3;

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($CompanyId);
        $customerGroup = $this->createMock(
            \Magento\Customer\Model\Group::class
        );
        $sortOrder = $this->createMock(
            SortOrder::class
        );
        $searchResults = $this->createMock(
            GroupSearchResultsInterface::class
        );
        $searchCriteria = $this->getMockForAbstractClass(
            SearchCriteriaInterface::class)
        ;
        $this->sortOrderBuilder->expects($this->once())
            ->method('setField')
            ->with(GroupInterface::CODE)
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('setAscendingDirection')
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('create')
            ->willReturn($sortOrder);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(GroupInterface::ID, GroupInterface::NOT_LOGGED_IN_ID, 'neq')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')
            ->with($sortOrder)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->groupRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn(new \ArrayIterator([$customerGroup]));
        $customerGroup->expects($this->once())
            ->method('getId')
            ->willReturn($groupId);
        $customerGroup->expects($this->once())
            ->method('getCode')->willReturn($groupName);

        $this->assertEquals(
            [
                [
                    'label' => $groupName,
                    'value' => $groupId
                ]
            ],
            $this->customerGroupSource->toOptionArray()
        );
    }

    /**
     * Test for toOptionArray method.
     *
     * @return void
     */
    public function testToOptionArrayNewCompany()
    {
        $groupId = 1;
        $groupName = 'Group Name';
        $CompanyId = null;

        $this->requestInterface->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($CompanyId);

        $customerGroup = $this->createMock(
            \Magento\Customer\Model\Group::class
        );
        $sortOrder = $this->createMock(
            SortOrder::class
        );
        $searchResults = $this->createMock(
            GroupSearchResultsInterface::class
        );
        $searchCriteria = $this->getMockForAbstractClass(
            SearchCriteriaInterface::class)
        ;
        $this->sortOrderBuilder->expects($this->once())
            ->method('setField')
            ->with(GroupInterface::CODE)
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('setAscendingDirection')
            ->willReturnSelf();
        $this->sortOrderBuilder->expects($this->once())
            ->method('create')
            ->willReturn($sortOrder);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with(GroupInterface::ID, GroupInterface::NOT_LOGGED_IN_ID, 'neq')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addSortOrder')
            ->with($sortOrder)->willReturnSelf();
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('create')
            ->willReturn($searchCriteria);
        $this->groupRepository->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResults);
        $searchResults->expects($this->once())
            ->method('getItems')
            ->willReturn(new \ArrayIterator([$customerGroup]));
        $customerGroup->expects($this->once())
            ->method('getId')
            ->willReturn($groupId);
        $customerGroup->expects($this->once())
            ->method('getCode')->willReturn($groupName);

        $this->assertEquals(
            [
                [
                    'label' => AdvancedSearchCustomerGroup::CREATE_NEW_LABEL,
                    'value' => AdvancedSearchCustomerGroup::CREATE_NEW_VALUE
                ],
                [
                    'label' => $groupName,
                    'value' => $groupId
                ]
            ],
            $this->customerGroupSource->toOptionArray()
        );
    }
}
