<?php

/**
 * Copyright © fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SelfReg\Plugin\Ui\Users;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\RequestInterface;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Eav\Model\ResourceModel\Entity\Attribute;
use Fedex\Login\Helper\Login;
use Fedex\Delivery\Helper\Data as DeliveryHelper;

class DataProvider
{
    /**
     * Data construct
     *
     * @param ToggleConfig $toggleConfig
     * @param RequestInterface $request
     * @param SelfReg $selfReg
     */
    public function __construct(
        private ToggleConfig $toggleConfig,
        private RequestInterface $request,
        private SelfReg $selfReg,
        private Attribute $eavAttribute,
        private Login $login,
        private DeliveryHelper $deliveryHelper
    )
    {
    }

    /**
     *
     * @param \Magento\Company\Ui\DataProvider\Users\DataProvider $subject
     * @param \Magento\Company\Model\ResourceModel\Users\Grid\Collection $result
     * @return \Magento\Company\Model\ResourceModel\Users\Grid\Collection
     */
    public function afterGetSearchResult($subject, $result)
    {
        $has_manage_user_permission=false;
        if($this->selfReg->toggleUserRolePermissionEnable()){
            $has_manage_user_permission= $this->deliveryHelper->checkPermission('manage_users');
        }
        if ($this->selfReg->isSelfRegCustomer() || $this->selfReg->isSelfRegCustomerAdmin() || $has_manage_user_permission) {
            $postData = $this->request->getParams();
            if (isset($postData['sorting']['field']) && !empty($postData['sorting']['field'])) {
                $field = $postData['sorting']['field'];
                if ($field == 'email') {
                    $field = 'secondary_email';
                }
                $direction = strtoupper($postData['sorting']['direction']);
                $result->getSelect()->order("$field $direction");
            } else {
                $result->getSelect()->order("name ASC");
            }

            if (isset($postData['search']) && !empty($postData['search'])) {
                $searchdata = $postData['search'];
                $result->getSelect()
                ->where("main_table.name like ? OR main_table.secondary_email like ? OR main_table.external_user_id like ?", "%".$searchdata."%");
            }
            if (isset($postData['filter'])) {
                if ($this->selfReg->toggleUserRolePermissionEnable()) {
                    $this->filterUser($result, $postData);
                } else {
                    if ($postData['filter'] != "") {
                        $result->getSelect()
                        ->where("IFNULL(main_table.customer_status, company_customer.status) IN (".$postData['filter'].")");
                    }
                }
            }
            return $result;
        }
        return $result;
    }

    /**
     * Filter User Data
     *
     * @param array $postData
     * @return void
     */
    public function filterUser($result, $postData) {
        if (!empty($postData['filter'])) {
            $attributeIds = [];
            $roleFilter = $postData['filter'];
            if (isset($roleFilter['status'])) {
                unset($roleFilter['status']);
            }
            $companyId = $this->login->getCompanyId();
            if (!empty($roleFilter)) {
                $customerIdData = $this->selfReg->getCompanyUserPermission($companyId, $roleFilter);

                if (is_array($customerIdData) && !empty($customerIdData)) {
                    $result->addFieldToFilter('entity_id', ['IN'=>$customerIdData]);
                } else {
                    $result->addFieldToFilter('entity_id', ['IN'=>[0]]);
                }
            }
            foreach($postData['filter'] as $key => $value) {

                if ($key == "status") {
                    $result->getSelect()->where("IFNULL(main_table.customer_status, company_customer.status) IN (".$value.")");
                    break;
                }
            }
        }
        return $result;
    }
}
