<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Plugin;

use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\LiveSearchAdapter\Model\QueryArgumentProcessor\FilterQueryArgumentProcessor;

class FilterQueryArgumentProcessorPlugin
{
    /**
     * @param InstoreConfig $instoreConfig
     */
    public function __construct(
        private InstoreConfig $instoreConfig
    ) {
    }

    /**
     * @param FilterQueryArgumentProcessor $subject
     * @param array $result
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function afterGetQueryArgumentValue(
        FilterQueryArgumentProcessor $subject,
        array $result,
        SearchCriteriaInterface $searchCriteria
    ): array {
        if (!$this->instoreConfig->getLivesearchCustomSharedCatalogId()) {
            return $result;
        }

        $sharedCatalogsFilter[] = [[
            'attribute' => 'shared_catalogs',
            'eq' => $this->instoreConfig->getLivesearchCustomSharedCatalogId()
        ]];

        return array_merge($result, ...$sharedCatalogsFilter);
    }
}
