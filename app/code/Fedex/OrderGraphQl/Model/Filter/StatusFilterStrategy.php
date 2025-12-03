<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Fedex\OrderGraphQl\Model\Resolver\DataProvider\OrderStatusMapping;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

class StatusFilterStrategy implements FilterStrategyInterface
{


    /**
     * @param FilterBuilder $filterBuilder
     * @param OrderStatusMapping $orderStatusMapping
     */
    public function __construct(
        private readonly FilterBuilder $filterBuilder,
        private readonly OrderStatusMapping $orderStatusMapping
    ) {
    }

    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        if (empty($filterMap['increment_id']))
        {
            $statusValues = [];
            foreach ($filterMap['status'] as $val) {
                foreach ($this->orderStatusMapping->getMappingValues($val) as $mappedStatus) {
                    if (!empty($mappedStatus)) {
                        $statusValues[] = $mappedStatus;
                    }
                }
            }
            $searchCriteria = $searchCriteria->addFilter(
                'status',
                $statusValues,
                'in'
            );
        }
        return $searchCriteria;
    }
}
