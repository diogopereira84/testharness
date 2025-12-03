<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);
namespace Fedex\CatalogMvp\HTTP\Client;

use Magento\Framework\HTTP\Client\Curl as CurlLibrary;

class Curl extends CurlLibrary
{
     /**
     * Make DELETE request
     *
     * The Magento Default curl library dosent support delete method
     *
     * @param string $uri
     * @return void
     * @codeCoverageIgnore
     */
    public function delete($uri)
    {
        $this->makeRequest("DELETE", $uri);

    }
}
