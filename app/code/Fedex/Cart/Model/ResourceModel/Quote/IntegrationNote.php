<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\ResourceModel\Quote;

use Fedex\Cart\Api\Data\CartIntegrationNoteInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class IntegrationNote extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = CartIntegrationNoteInterface::QUOTE_INTEGRATION_NOTE_ID;

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CartIntegrationNoteInterface::ENTITY, CartIntegrationNoteInterface::QUOTE_INTEGRATION_NOTE_ID);
    }
}
