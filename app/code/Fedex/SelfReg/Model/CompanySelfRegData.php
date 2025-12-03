<?php
/**
 * B-1257375 - Add configuration for site access
 * (use access flow- all registered users, users from specific domain and admin approval)
 *
 */

namespace Fedex\SelfReg\Model;

use Fedex\SelfReg\Model\ResourceModel\CompanySelfRegData as CompanySelfRegDataResourceModel;
use Magento\Framework\Model\AbstractModel;

class CompanySelfRegData extends AbstractModel
{
    /**
     * Company id field name
     */
    const COMPANY_ID = 'company_id';

    /**
     * Initialize model
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CompanySelfRegDataResourceModel::class);
    }
}
