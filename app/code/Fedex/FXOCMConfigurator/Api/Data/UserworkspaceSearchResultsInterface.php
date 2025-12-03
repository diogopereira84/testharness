<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\FXOCMConfigurator\Api\Data;

/**
 * @codeCoverageIgnore
 */

interface UserworkspaceSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get userworkspace list.
     * @return \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface[]
     */
    public function getItems();

    /**
     * Set customer_id list.
     * @param \Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

