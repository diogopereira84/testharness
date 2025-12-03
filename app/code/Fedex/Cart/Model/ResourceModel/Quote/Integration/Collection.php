<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Model\ResourceModel\Quote\Integration;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     * phpcs:disable
     */
    protected $idFieldName = 'integration_id';

    /**
     * Collection initialisation
     * phpcs:disable
     */
    protected function _construct()
    {
        $this->_init(
            'Fedex\Cart\Model\Quote\Integration',
            'Fedex\Cart\Model\ResourceModel\Quote\Integration'
        );
    }
}
