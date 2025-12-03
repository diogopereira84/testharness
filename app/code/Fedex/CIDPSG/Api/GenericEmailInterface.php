<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface GenericEmailInterface
{
    /**
     * Use to publish email content
     *
     * @return void
     **/
    public function publishGenericEmail();
}
