<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\GTNFilterStrategy;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\FilterBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;

class GTNFilterStrategyTest extends TestCase
{
    /** @var MockObject|SearchCriteriaBuilder */
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;

    /** @var FilterBuilder */
    private FilterBuilder $filterBuilder;

    /** @var Filter  */
    private Filter $filter;

    /** @var GTNFilterStrategy  */
    private GTNFilterStrategy $object;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->filterBuilder = $this->createMock(FilterBuilder::class);
        $this->filter = $this->createMock(Filter::class);

        $this->object = new GTNFilterStrategy(
            $this->filterBuilder,
        );
    }

    /**
     * @return void
     */
    public function testApplyFilter(){
        $this->filterBuilder->expects($this->atLeastOnce())
            ->method('setField')
            ->willReturn($this->filterBuilder);
        $this->filterBuilder->expects($this->atLeastOnce())
            ->method('setValue')
            ->willReturn($this->filterBuilder);
        $this->filterBuilder->expects($this->atLeastOnce())
            ->method('setConditionType')
            ->willReturn($this->filterBuilder);
        $this->filterBuilder->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->filter);
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilters')
            ->willReturn($this->searchCriteriaBuilder);
        $this->object->applyFilter(
            json_decode($this->getFilterMap(), true),
            $this->searchCriteriaBuilder
        );
    }

    /**
     * @return string
     */
    private function getFilterMap() {
        return '{"created_at":{"from":"2023-01-22 00:00:00","to":"2024-01-23 00:00:00"},"customer_email":"user@usermail.com","customer_firstname":"Personame","customer_lastname":"Lastname","increment_id":"","location_id":"","omni_attributes":[],"shipping_due_date":{"from":"2023-01-22 00:00:00","to":"2024-01-23 00:00:00"},"status":["CANCELLED","CONFIRMED","DELIVERED","READY_FOR_PICKUP","RECEIVED","SHIPPED"],"telephone":[{"phoneNumber":{"extension":"123","number":"8045645820"},"usage":"PRIMARY"}]}';
    }
}
