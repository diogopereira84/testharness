<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class IncrementIdFilterStrategy implements FilterStrategyInterface
{
    /** @var string[]  */
    const OMNI_ALLOWED_ATTRIBUTES = ['ORDERNUMBER'];

    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     * @throws GraphQlInputException
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        if (!empty($filterMap['increment_id'])) {
            if (count($filterMap['omni_attributes']) !== 1 ||
                !in_array($filterMap['omni_attributes'][0], self::OMNI_ALLOWED_ATTRIBUTES)) {
                throw new GraphQlInputException(__('Invalid omni attributes.'));
            }
            $searchCriteria = $searchCriteria->addFilter(
                'main_table.increment_id',
                $filterMap['increment_id']
            );
        }
        return $searchCriteria;
    }
}
