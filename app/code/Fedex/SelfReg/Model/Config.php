<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Model;

use Fedex\SelfReg\Api\Data\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config.
 * Provide access to SelfReg Module database configuration.
 */
class Config implements ConfigInterface
{
    /**
     * XML path for Add Catalog Item notification message
     */
    public const XML_PATH_ADD_CATALOG_ITEM_MESSAGE = 'selfreg_setting/notification_setting/add_item';

    /**
     * XML path for Move Catalog Item notification message
     */
    public const XML_PATH_MOVE_CATALOG_ITEM_MESSAGE = 'selfreg_setting/notification_setting/move_item';

    /**
     * XML path for Delete Catalog Item notification message
     */
    public const XML_PATH_DELETE_CATALOG_ITEM_MESSAGE = 'selfreg_setting/notification_setting/delete_item';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getAddCatalogItemMessage(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ADD_CATALOG_ITEM_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getMoveCatalogItemMessage(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MOVE_CATALOG_ITEM_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function getDeleteCatalogItemMessage(): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DELETE_CATALOG_ITEM_MESSAGE,
            ScopeInterface::SCOPE_STORE
        );
    }
}
