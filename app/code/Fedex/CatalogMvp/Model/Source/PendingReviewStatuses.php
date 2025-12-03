<?php

/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Model\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class PendingReviewStatuses extends AbstractSource
{
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $this->_options = [
            ['value' => 0, 'label' => ' '],
            ['value' => 1, 'label' => 'Pending review'],
            ['value' => 2, 'label' => 'Ready for review'],
            ['value' => 3, 'label' => 'Ready for order']
        ];

        return $this->_options;
    }
}
