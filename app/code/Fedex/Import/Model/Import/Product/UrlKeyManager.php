<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Model\Import\Product;

use Fedex\Import\Api\UrlKeyManagerInterface;

class UrlKeyManager implements UrlKeyManagerInterface
{
    /**
     * @var array
     */
    protected $importUrlKeys = [];

    /**
     * To add url keys
     *
     * @param string $sku
     * @param string $urlKey
     *
     * @return $this|mixed
     */
    public function addUrlKeys($sku, $urlKey)
    {
        if (!isset($this->importUrlKeys[$urlKey])) {
            $this->importUrlKeys[$urlKey] = $sku;
        }

        return $this;
    }

    /**
     * To get url keys
     *
     * @return array
     */
    public function getUrlKeys()
    {
        return $this->importUrlKeys;
    }

    /**
     * To check if url key exist
     *
     * @param string $sku
     * @param string $urlKey
     *
     * @return bool|mixed
     */
    public function isUrlKeyExist($sku, $urlKey)
    {
        if (isset($this->importUrlKeys[$urlKey]) && $this->importUrlKeys[$urlKey] !== $sku) {
            return true;
        }
        return false;
    }
}
