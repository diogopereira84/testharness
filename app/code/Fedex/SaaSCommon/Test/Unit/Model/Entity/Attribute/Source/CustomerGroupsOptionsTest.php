<?php

declare(strict_types=1);

namespace Fedex\SaaSCommon\Test\Unit\Model\Entity\Attribute\Source;

use Fedex\SaaSCommon\Model\Entity\Attribute\Source\CustomerGroupsOptions;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class CustomerGroupsOptionsTest extends TestCase
{
    private $groupRepositoryMock;
    private $searchCriteriaBuilderMock;
    private $searchCriteriaMock;
    private $groupListMock;

    protected function setUp(): void
    {
        $this->groupRepositoryMock = $this->createMock(GroupRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteriaMock = $this->createMock(SearchCriteriaInterface::class);
        $this->groupListMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getItems'])
            ->getMock();
    }

    public function testGetAllOptionsReturnsCorrectArray()
    {
        $customerGroup1 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCode', 'getId'])
            ->getMock();
        $customerGroup1->method('getCode')->willReturn('General');
        $customerGroup1->method('getId')->willReturn(1);

        $customerGroup2 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getCode', 'getId'])
            ->getMock();
        $customerGroup2->method('getCode')->willReturn('Wholesale');
        $customerGroup2->method('getId')->willReturn(2);

        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->with($this->searchCriteriaMock)->willReturn($this->groupListMock);
        $this->groupListMock->method('getItems')->willReturn([$customerGroup1, $customerGroup2]);

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $expected = [
            ['label' => 'All Groups', 'value' => '-1'],
            ['label' => 'General', 'value' => 1],
            ['label' => 'Wholesale', 'value' => 2],
        ];

        $this->assertSame($expected, $model->getAllOptions());
    }

    public function testGetAllOptionsReturnsEmptyArray()
    {
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->willReturn($this->groupListMock);
        $this->groupListMock->method('getItems')->willReturn([]);

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $this->assertSame([[
            'label' => 'All Groups',
            'value' => '-1',
        ]], $model->getAllOptions());
    }

    public function testGetAllOptionsValuesReturnsCorrectArray()
    {
        $customerGroup1 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $customerGroup1->method('getId')->willReturn(1);

        $customerGroup2 = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $customerGroup2->method('getId')->willReturn(2);

        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->willReturn($this->groupListMock);
        $this->groupListMock->method('getItems')->willReturn([$customerGroup1, $customerGroup2]);

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $expected = ['-1', 1, 2];

        $this->assertSame($expected, $model->getAllOptionsValues());
    }

    public function testGetAllOptionsValuesReturnsEmptyArray()
    {
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->willReturn($this->groupListMock);
        $this->groupListMock->method('getItems')->willReturn([]);

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $this->assertSame(['-1'], $model->getAllOptionsValues());
    }

    public function testGetAllOptionsThrowsLocalizedException()
    {
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->willThrowException(new LocalizedException(__('Error')));

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $this->expectException(LocalizedException::class);
        $model->getAllOptions();
    }

    public function testGetAllOptionsValuesThrowsLocalizedException()
    {
        $this->searchCriteriaBuilderMock->method('create')->willReturn($this->searchCriteriaMock);
        $this->groupRepositoryMock->method('getList')->willThrowException(new LocalizedException(__('Error')));

        $model = new CustomerGroupsOptions($this->groupRepositoryMock, $this->searchCriteriaBuilderMock);

        $this->expectException(LocalizedException::class);
        $model->getAllOptionsValues();
    }
}
