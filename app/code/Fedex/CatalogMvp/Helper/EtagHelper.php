<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Catalog\Model\Category;

class EtagHelper extends AbstractHelper
{
    /**
     * Generate the ETag value based on category data
     *
     * @param Category $category
     * @return string
     */
    public function generateEtag(Category $category): string
    {
        return substr(hash('sha256', $category->getData('name') . $category->getId() . time()), 0, 32);
    }
}
