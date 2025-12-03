<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class EmailFilterStrategy implements FilterStrategyInterface
{
    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        if (empty($filterMap['increment_id'])) {
            $searchCriteria = $searchCriteria->addFilter(
                'customer_email',
                $filterMap['customer_email'],
                'in'
            );
        }
        return $searchCriteria;
    }
}
