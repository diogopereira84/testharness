<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\IncrementIdFilterStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\SearchCriteriaBuilder;

class IncrementIdFilterStrategyTest extends TestCase
{
    /** @var MockObject|SearchCriteriaBuilder */
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;

    /** @var IncrementIdFilterStrategy  */
    private IncrementIdFilterStrategy $object;

    public function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->object = new IncrementIdFilterStrategy();
    }

    public function testApplyFilter(){

        $this->searchCriteriaBuilder->expects($this->atLeastOnce())
            ->method('addFilter')
            ->willReturn($this->searchCriteriaBuilder);
        $this->object->applyFilter(
            json_decode($this->getFilterMap(), true),
            $this->searchCriteriaBuilder
        );
    }

    /**
     * @return string
     */
    private function getFilterMap(): string
    {
        return '{"created_at":[],"customer_email":"","customer_firstname":"","customer_lastname":"","increment_id":"2020555147881526","location_id":"5747","omni_attributes":["ORDERNUMBER"],"shipping_due_date":[],"status":"","telephone":""}';
    }
}
