<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\Quote;

use Fedex\Cart\Api\Data\CartIntegrationItemInterface;
use Magento\Framework\Model\AbstractModel;

class IntegrationItem extends AbstractModel implements CartIntegrationItemInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'quote_integration_item';

    /**
     * Initialise resource model
     */
    protected function _construct()
    {
        $this->_init('Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem');
    }

    /**
     * @inheritDoc
     */
    public function getIntegrationItemId(): int
    {
        return $this->getData(CartIntegrationItemInterface::KEY_INTEGRATION_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setIntegrationItemId(int $integrationItemId): static
    {
        return $this->setData(CartIntegrationItemInterface::KEY_INTEGRATION_ITEM_ID, $integrationItemId);
    }

    /**
     * @inheritDoc
     */
    public function getItemId(): int
    {
        return (int) $this->getData(CartIntegrationItemInterface::KEY_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setItemId(int $itemId): static
    {
        return $this->setData(CartIntegrationItemInterface::KEY_ITEM_ID, $itemId);
    }

    /**
     * @inheritDoc
     */
    public function getItemData(): string
    {
        return $this->getData(CartIntegrationItemInterface::KEY_ITEM_DATA);
    }

    /**
     * @inheritDoc
     */
    public function setItemData(string $itemData): static
    {
        return $this->setData(CartIntegrationItemInterface::KEY_ITEM_DATA, $itemData);
    }
}
