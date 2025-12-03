<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 */
namespace Fedex\CategoryLayout\Block;

use Fedex\CatalogMvp\Model\Source\SharedCatalogs;

/**
 * Class CategoryUpdate
 * @api
 * @package Fedex\CategoryLayout\Block
 * @since 100.0.2
 */
class CategoryUpdate extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param SharedCatalogs $sharedCatalogs
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        private readonly SharedCatalogs $sharedCatalogs,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    /**
     * Get browse catalogs categories
     */
    public function getCategories()
    {
        return $this->sharedCatalogs->toOptionArray();
    }
    
}
