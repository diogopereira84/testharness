<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Api\Data;

interface CustomerPunchoutUniqueIdSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return CustomerPunchoutUniqueIdInterface[]
     */
    public function getItems();

    /**
     * @param CustomerPunchoutUniqueIdInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
