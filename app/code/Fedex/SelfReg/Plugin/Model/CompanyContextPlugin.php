<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Plugin\Model;

use Magento\Company\Model\CompanyContext as subject;
use Fedex\Delivery\Helper\Data;

class CompanyContextPlugin
{
    private Data $deliveryDataHelper;

    /**
     * CompanyContextPlugin Constructor
     *
     * @param Data $deliveryhelperData
     */
    public function __construct(
        Data $deliveryhelperData
    ) {
        $this->deliveryDataHelper = $deliveryhelperData;
    }

    /**
     * Check permission for manage user
     *
     * @param Subject $subject
     * @param result
     * @param $resource
     *
     * @return boolean
     */
    public function afterIsResourceAllowed(Subject $subject, $result, $resource)
    {
        $isRolesAndPermissionEnabled = $this->deliveryDataHelper->getToggleConfigurationValue('change_customer_roles_and_permissions');

        if ($isRolesAndPermissionEnabled && !$result && str_contains($resource, "Magento_Company::user")) {
            $result = $this->deliveryDataHelper->checkPermission('manage_users');
        }

        return $result;
    }
}
