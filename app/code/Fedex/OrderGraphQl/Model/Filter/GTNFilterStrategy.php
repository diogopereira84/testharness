<?php
namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Fedex\Punchout\Helper\Data as PunchoutHelper;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;

class GTNFilterStrategy implements FilterStrategyInterface
{
    /** @var array */
    const ALLOWED_GTN_PREFIX_NUMBERS = [
        PunchoutHelper::DEFAULT_GTN_PREFIX,
        PunchoutHelper::INSTORE_GTN_PREFIX,
    ];

    /**
     * @param FilterBuilder $filterBuilder
     */
    public function __construct(
        private readonly FilterBuilder $filterBuilder,
    ) {
    }

    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        if (empty($filterMap['increment_id'])) {
            $filterGroups = [];
            foreach (self::ALLOWED_GTN_PREFIX_NUMBERS as $prefixNumber) {
                $filter = $this->filterBuilder
                    ->setField('main_table.increment_id')
                    ->setValue($prefixNumber . '%')
                    ->setConditionType('like')
                    ->create();

                $filterGroups[] = $filter;
            }
            $searchCriteria = $searchCriteria->addFilters($filterGroups);
        }

        return $searchCriteria;
    }
}
