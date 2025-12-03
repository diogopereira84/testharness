<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\CIDPSG\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\CIDPSG\Model\ResourceModel\PsgCustomerFields as PsgCustomerFieldsResourceModel;

/**
 * Class for PsgCustomerFields
 */
class PsgCustomerFields extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(PsgCustomerFieldsResourceModel::class);
    }
}
