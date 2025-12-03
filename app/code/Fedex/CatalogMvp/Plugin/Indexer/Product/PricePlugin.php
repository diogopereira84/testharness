<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\Indexer\Product;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Catalog\Model\Indexer\Product\Price;

class PricePlugin
{

    /**
     * Around Plugin for Execute Method
     * @param Price $subject
     * @param callable $proceed
     * @param int[] $ids
     * @return bool|callable
     */
    public function aroundExecute(
        Price $subject,
        callable $proceed,
        $ids
    ) {
        
        return false;  
    }

    /**
     * Around Plugin for ExecuteFull Method
     * @param Price $subject
     * @param callable $proceed
     * @return bool|callable
     */
    public function aroundExecuteFull(
        Price $subject,
        callable $proceed
    ) {
        
        return false;
        
    }

    /**
     * Around Plugin for ExecuteList Method
     * @param Price $subject
     * @param callable $proceed
     * @param int[] $ids
     * @return bool|callable
     */
    public function aroundExecuteList(
        Price $subject,
        callable $proceed,
        $ids
    ) {
        
        return false;
        
    }

    /**
     * Around Plugin for ExecuteRow Method
     * @param Price $subject
     * @param callable $proceed
     * @param int $id
     * @return bool|callable
     */
    public function aroundExecuteRow(
        Price $subject,
        callable $proceed,
        $id
    ) {
        
        return false;
        
    }
}
