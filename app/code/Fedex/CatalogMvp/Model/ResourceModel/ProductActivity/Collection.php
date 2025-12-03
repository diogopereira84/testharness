<?php

namespace Fedex\CatalogMvp\Model\ResourceModel\ProductActivity;

use Fedex\CatalogMvp\Model\ProductActivity as ProductActivityModel;
use Fedex\CatalogMvp\Model\ResourceModel\ProductActivity as ProductActivityResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ProductActivityModel::class, ProductActivityResourceModel::class);
    }
}
