<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Import\Product\Validator;

use Magento\Store\Model\StoreManagerInterface;

/**
 * Model Class PageConfig
 */
class PageConfig
{
    /**
     * @var array
     */
    protected $defaultModes = [
        'PRODUCTS',
        'PAGE',
        'PRODUCTS_AND_PAGE'
    ];

    /**
     * PageConfig constructor
     *
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * Validate store id
     *
     * @param string|mixed $data
     * @return bool
     */
    public function validateStoreId($data)
    {
        $storeList = array_keys($this->storeManager->getStores(true));
        if (!is_numeric($data) || (int)$data != $data) {
            return false;
        }
        if (array_search($data, $storeList) === false) {
            return false;
        }
        return true;
    }

    /**
     * Validate mode
     *
     * @param string|mixed $data
     * @return bool
     */
    public function validateMode($data)
    {
        if (array_search($data, $this->defaultModes) === false) {
            return false;
        }
        return true;
    }

    /**
     * Get default store id
     *
     * @return mixed
     */
    public function getDefaultStoreId()
    {
        return $this->storeManager->getDefaultStoreView()->getId();
    }
}
