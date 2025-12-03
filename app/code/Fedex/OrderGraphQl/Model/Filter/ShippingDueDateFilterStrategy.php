<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ShippingDueDateFilterStrategy implements FilterStrategyInterface
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
                'shipping_due_date',
                $filterMap['shipping_due_date']['from'],
                'gteq'
            );

            $searchCriteria = $searchCriteria->addFilter(
                'shipping_due_date',
                $filterMap['shipping_due_date']['to'],
                'lteq'
            );
        }
        return $searchCriteria;
    }
}
