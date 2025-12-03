<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected string $idFieldName = 'integration_item_id';

    /**
     * Collection initialisation
     */
    protected function _construct()
    {
        $this->_init(
            'Fedex\Cart\Model\Quote\IntegrationItem',
            'Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem'
        );
    }
}
