<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class LastnameFilterStrategy implements FilterStrategyInterface
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
                'customer_lastname',
                $filterMap['customer_lastname'],
                'in'
            );
        }
        return $searchCriteria;
    }
}
