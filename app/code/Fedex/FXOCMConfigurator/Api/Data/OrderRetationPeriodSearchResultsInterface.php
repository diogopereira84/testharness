<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api\Data;

interface OrderRetationPeriodSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get OrderRetationPeriod list.
     * @return \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

