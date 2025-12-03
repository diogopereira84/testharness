<?php

namespace Fedex\OrderGraphQl\Model\Filter;

use Fedex\OrderGraphQl\Model\Filter\Interface\FilterStrategyInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class TelephoneFilterStrategy implements FilterStrategyInterface
{
    /** @var string[]  */
    const PHONE_ALLOWED_USAGES = ['PRIMARY'];

    /**
     * @param array $filterMap
     * @param SearchCriteriaBuilder $searchCriteria
     * @return SearchCriteriaBuilder
     * @throws GraphQlInputException
     */
    public function applyFilter(array $filterMap, SearchCriteriaBuilder $searchCriteria): SearchCriteriaBuilder
    {
        $tk4397896toggleEnabled = $filterMap['tiger_tk4397896_toggle_enabled'] ?? false;
        if (empty($filterMap['increment_id'])) {
            $telephones = [];
            foreach ($filterMap['telephone'] as $telephone) {
                if(!isset($telephone['usage']) ||
                    !in_array($telephone['usage'], self::PHONE_ALLOWED_USAGES)) {
                    throw new GraphQlInputException(__('Invalid phone usage.'));
                }
                if($tk4397896toggleEnabled){
                   $telephones[] = $telephone['phoneNumber']['number'];
                }else{
                    $telephones[] = $telephone;
                }
            }
            if (count($telephones) > 0) {
                if($tk4397896toggleEnabled){
                    $searchCriteria->addFilter('custom_telephone_filter', $telephones, 'in');
                }else{
                    $searchCriteria = $searchCriteria->addFilter('telephone',$telephones,'in');
                }
            }
        }
        return $searchCriteria;
    }
}
