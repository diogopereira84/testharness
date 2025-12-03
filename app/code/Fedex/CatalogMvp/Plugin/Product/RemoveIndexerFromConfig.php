<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\Product;

use Magento\Indexer\Model\Config;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\CatalogPermissions\Model\Indexer\Product\Processor as ProductProcessor;
use Magento\CatalogPermissions\Model\Indexer\Category\Processor as CategoryProcessor;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class RemoveIndexerFromConfig
 *
 * Remove the Product Price Indexer from config
 */
class RemoveIndexerFromConfig
{
    /**
     * Data Class Constructor.
     *
     * @param CatalogMvp $helper
     */
    public function __construct(
        protected CatalogMvp $helper
    )
    {
    }

    /**
     * Intercept indexers and remove the product price indexer from the list
     * So the product price is not reindexed when bin/magento indexer:reindex is run
     *
     * @param Config $subject
     * @param array[] $result
     * @return array[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetIndexers(Config $subject, array $result): array
    {
            if ($result[Processor::INDEXER_ID]) {
                unset($result[Processor::INDEXER_ID]);
            }
    
            if ($result[ProductProcessor::INDEXER_ID]) {
                unset($result[ProductProcessor::INDEXER_ID]);
            }
    
            if ($result[CategoryProcessor::INDEXER_ID]) {
                unset($result[CategoryProcessor::INDEXER_ID]);
            }

        return $result;
    }
}
