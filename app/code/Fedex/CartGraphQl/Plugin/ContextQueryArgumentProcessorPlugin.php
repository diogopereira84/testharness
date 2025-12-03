<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\ContextQueryArgumentProcessor;

class ContextQueryArgumentProcessorPlugin
{
    /**
     * @param ContextQueryArgumentProcessor $subject
     * @param array $result
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function afterGetQueryArgumentValue(
        ContextQueryArgumentProcessor $subject,
        array $result,
        SearchCriteriaInterface $searchCriteria
    ): array {
        return [];
    }
}
