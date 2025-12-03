<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class CreatedAtFilterStrategy implements FilterStrategyInterface
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
                'main_table.created_at',
                $filterMap['created_at']['from'],
                'gteq'
            );

            $searchCriteria = $searchCriteria->addFilter(
                'main_table.created_at',
                $filterMap['created_at']['to'],
                'lteq'
            );
        }
        return $searchCriteria;
    }
}
