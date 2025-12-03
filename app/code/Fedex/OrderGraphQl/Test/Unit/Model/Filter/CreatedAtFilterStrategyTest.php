<?php

namespace Fedex\OrderGraphQl\Test\Unit\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\CreatedAtFilterStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CreatedAtFilterStrategyTest extends TestCase
{
    /** @var MockObject|SearchCriteriaBuilder */
    private SearchCriteriaBuilder|MockObject $searchCriteriaBuilder;

    /** @var CreatedAtFilterStrategy  */
    private CreatedAtFilterStrategy $object;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->object = new CreatedAtFilterStrategy();
    }

    /**
     * @return void
     */
    public function testApplyFilter(){
        $this->searchCriteriaBuilder->expects($this->exactly(2))
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
        return '{"created_at":{"from":"2023-01-22 00:00:00","to":"2024-01-23 00:00:00"},"customer_email":"user@usermail.com","customer_firstname":"Personame","customer_lastname":"Lastname","increment_id":"","location_id":"","omni_attributes":[],"shipping_due_date":{"from":"2023-01-22 00:00:00","to":"2024-01-23 00:00:00"},"status":["CANCELLED","CONFIRMED","DELIVERED","READY_FOR_PICKUP","RECEIVED","SHIPPED"],"telephone":[{"phoneNumber":{"extension":"123","number":"8045645820"},"usage":"PRIMARY"}]}';
    }
}
