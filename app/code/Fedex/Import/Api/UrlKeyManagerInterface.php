<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Import\Api;

interface UrlKeyManagerInterface
{
    /**
     * To add url keys
     *
     * @param string $sku
     * @param string $urlKey
     *
     * @return mixed
     */
    public function addUrlKeys($sku, $urlKey);

    /**
     * To get url keys
     *
     * @return mixed
     */
    public function getUrlKeys();

    /**
     * To check if url key exist
     *
     * @param string $sku
     * @param string $urlKey
     *
     * @return mixed
     */
    public function isUrlKeyExist($sku, $urlKey);
}
