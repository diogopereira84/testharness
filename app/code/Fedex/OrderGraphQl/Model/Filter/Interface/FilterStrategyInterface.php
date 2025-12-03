<?php

declare(strict_types=1);

namespace Fedex\OrderGraphQl\Model\Filter\Interface;

use Magento\Framework\Api\SearchCriteriaBuilder;

interface FilterStrategyInterface
{
    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder;
}
