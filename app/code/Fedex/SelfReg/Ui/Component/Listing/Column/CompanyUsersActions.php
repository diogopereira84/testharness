<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\SelfReg\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;
use Fedex\SelfReg\ViewModel\CompanyUser;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Model\ParentUserGroupFactory;
use Magento\Customer\Model\Group;

/**
 * Class CompanyUsersActions.
 */
class CompanyUsersActions extends Column
{
    /**
     * Customer status Active.
     *
     * @var string
     */
    private $customerStatusActive = 'Active';
	
    /**
     * @var ToggleConfig
     */
    private $toggleConfig;

    /**
     * CompanyUsersActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Company\Api\RoleManagementInterface $roleManagement
     * @param \Magento\Company\Api\AuthorizationInterface $authorization
     * @param ToggleConfig $toggleConfig
     * @param ParentUserGroupFactory $parentUserGroupFactory
     * @param Group $customerGroupModel
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        /**
         * Url interface.
         */
        private UrlInterface $urlBuilder,
        private \Magento\Company\Api\RoleManagementInterface $roleManagement,
        private \Magento\Company\Api\AuthorizationInterface $authorization,
        ToggleConfig $toggleConfig,
        private ParentUserGroupFactory $parentUserGroupFactory,
        private Group $customerGroupModel,
        private CompanyUser $companyUser,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = 'group_id';
            foreach ($dataSource['data']['items'] as &$item) {
                $getUrl = $this->urlBuilder->getUrl('company/customer/get');
                $provider = 'company_users_listing.company_users_listing_data_source';
                $isRolesAndPermissionEnabled = $this->companyUser->toggleCustomerRolesAndPermissions();
                $editUserlabel="Edit";
                if($isRolesAndPermissionEnabled){
                    $editUserlabel="Edit User";
                }

                if (isset($item[$fieldName])) {
                    $userParentGroupId = $this->getParentGroupId($item[$fieldName]);
                    if ($userParentGroupId) {
                        $item['customer_group_id'] = 'Default';
                    } else {
                        $groupName = trim($this->getUserGroupName($item[$fieldName]));
                        if (isset($groupName) && !empty($groupName)) {
                            $item['customer_group_id'] = trim(preg_replace('/<.*?>\s*/', '', $groupName));
                        }
                    }
                }

                $item[$this->getData('name')]['edit'] = [
                    'href' => '#',
                    'label' => __($editUserlabel),
                    'hidden' => false,
                    'type' => 'edit-user',
                    'options' => [
                        'getUrl' => $getUrl,
                        'getUserUrl' => $getUrl . '?customer_id=' . $item['entity_id'],
                        'saveUrl' => $this->urlBuilder->getUrl('company/customer/manage'),
                        'id' => $item['entity_id'],
                        'gridProvider' => $provider,
                        'adminUserRoleId' => $this->roleManagement->getCompanyAdminRoleId(),
                        'userGroupName' => $item['customer_group_id'],
                    ],
                ];
				
                $deleteUrl = null;
                $setInactiveUrl = null;
                
                
                $setInactiveUrl = $this->urlBuilder->getUrl('selfreg/customer/delete');
                $deleteUrl = $this->urlBuilder->getUrl('selfreg/customer/permanentDelete');
                
				
                $item[$this->getData('name')]['delete'] = [
                    'href' => '#',
                    'label' => __('Delete'),
                    'hidden' => false,
                    'id' => $item['entity_id'],
                    'type' => 'delete-user',
                    'options' => [
                        'setInactiveUrl' => $setInactiveUrl,
                        'deleteUrl' => $deleteUrl,
                        'id' => $item['entity_id'],
                        'gridProvider' => $provider,
                        'inactiveClass' => $this->getSetInactiveButtonClass($item),
                    ],
                ];
            }
        }

        return $dataSource;
    }

    /**
     * Get set inactive button class.
     *
     * @param array $userData
     * @return string
     */
    private function getSetInactiveButtonClass(array $userData)
    {
        return ($this->isShowSetInactiveButton($userData)) ? '' : '_hidden';
    }

    /**
     * Is show set inactive button.
     *
     * @param array $userData
     * @return bool
     */
    private function isShowSetInactiveButton(array $userData)
    {
        return (!empty($userData['status']) && $userData['status']->getText() == $this->customerStatusActive);
    }

    /**
     * Get Parent Group id
     *
     * @param int $groupId
     * @return int
     */
    public function getParentGroupId($groupId)
    {
        $collection = $this->parentUserGroupFactory->create()
            ->getCollection()
            ->addFieldToFilter('parent_group_id', ['eq' => $groupId])
            ->getFirstItem();
        return $collection->getParentGroupId();
    }

    /**
     * Get user group name
     *
     * @param string $userGroupId
     * @return string
     */
    public function getUserGroupName($userGroupId)
    {
        $collection = $this->customerGroupModel
            ->getCollection()
            ->addFieldToFilter('customer_group_id', ['eq' => $userGroupId])
            ->getFirstItem();
        return $collection->getCustomerGroupCode();
    }
}
