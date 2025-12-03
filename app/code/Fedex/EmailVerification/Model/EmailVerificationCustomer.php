<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Model;

use Magento\Framework\Model\AbstractModel;
use Fedex\EmailVerification\Model\ResourceModel\EmailVerificationCustomer as ResourceModel;

/**
 * EmailVerificationCustomer Model
 */
class EmailVerificationCustomer extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }
}
