<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin\Indexer;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\CatalogPermissions\Model\Indexer\Category;

class CategoryPlugin
{

    /**
     * Around Plugin for Execute Method
     * @param Category $subject
     * @param callable $proceed
     * @param int[] $ids
     * @return bool|callable
     */
    public function aroundExecute(
        Category $subject,
        callable $proceed,
        $ids
    ) {
        
        return false;
        
    }

    /**
     * Around Plugin for ExecuteFull Method
     * @param Category $subject
     * @param callable $proceed
     * @return bool|callable
     */
    public function aroundExecuteFull(
        Category $subject,
        callable $proceed
    ) {
        return false;
    }

    /**
     * Around Plugin for ExecuteList Method
     * @param Category $subject
     * @param callable $proceed
     * @param int[] $ids
     * @return bool|callable
     */
    public function aroundExecuteList(
        Category $subject,
        callable $proceed,
        $ids
    ) {
        return false;
    }

    /**
     * Around Plugin for ExecuteRow Method
     * @param Category $subject
     * @param callable $proceed
     * @param int $id
     * @return bool|callable
     */
    public function aroundExecuteRow(
        Category $subject,
        callable $proceed,
        $id
    ) {
        return false;
    }
}
