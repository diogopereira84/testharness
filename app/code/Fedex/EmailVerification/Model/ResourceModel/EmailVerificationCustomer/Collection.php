<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Model\ResourceModel\EmailVerificationCustomer;

use Fedex\EmailVerification\Model\ResourceModel\EmailVerificationCustomer as EmailVerificationCustomerResourceModel;
use Fedex\EmailVerification\Model\EmailVerificationCustomer as EmailVerificationCustomerModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initilize resource model and model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(EmailVerificationCustomerModel::class, EmailVerificationCustomerResourceModel::class);
    }
}
