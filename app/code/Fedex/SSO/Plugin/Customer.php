<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Plugin;

use Magento\Framework\App\Request\Http;
/**
 * Customer plugin class
 */
class Customer
{

    /**
     * @param Http $request
     */
    public function __construct(
        protected Http $request
    )
    {
    }
    /**
     * Get secondary email if exist for fcl user
     *
     * @param object $subject
     * @param string $result
     *
     * @return string
     */
    public function afterGetEmail(\Magento\Customer\Model\Data\Customer $subject, $result)
    {
        
        $actionName = $this->request->getFullActionName();
        $skipActions = ['company_customer_get','selfreg_customer_save','emailverification_index_index', 'selfreg_customer_bulksave'];
        if ($subject->getCustomAttribute('secondary_email') &&
        !in_array($actionName,$skipActions)) {
            $result = $subject->getCustomAttribute('secondary_email')->getValue();
        }
        return $result;
    }
}
