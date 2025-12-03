<?php

namespace Fedex\SelfReg\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Fedex\SelfReg\Model\EnhanceRolePermissionFactory;

class InsertPermissions implements DataPatchInterface
{
    public function __construct(
        private EnhanceRolePermissionFactory $enhanceRolePermission
    )
    {
    }

    public function apply()
    {
        $permisisonData = [
            [
                'label' => 'Shared Orders::shared_orders',
                'sort_order' => '1',
                'tooltip' => "Users with this permission will be able to access the 'Shared Orders' tab to view and export order history for this site."
            ],
            [
                'label' => 'Shared Credit Cards::shared_credit_cards',
                'sort_order' => '2',
                'tooltip' => 'Users with this access will be able to add, edit or delete credit card information for this site.'
            ],
            [
                'label' => 'Manage Users::manage_users',
                'sort_order' => '3',
                'tooltip' => "Users with this permission will have access to the 'Manage Users' tab to edit user permissions and manage user access."
            ],
            [
                'label' => 'Manage Catalog::manage_catalog',
                'sort_order' => '4',
                'tooltip' => "Users with this permission will have the ability to manage all catalog documents and folders for this site."
            ],
            [
                'label' => 'Yes::email_allow::manage_users',
                'sort_order' => '0',
                'tooltip' => "Would you like this user to receive site access approval emails?"
            ],
            [
                'label' => 'No::email_deny::manage_users',
                'sort_order' => '0',
                'tooltip' => "Would you like this user to receive site access approval emails?"
            ]
        ];
        foreach ($permisisonData as $data) {
            $this->enhanceRolePermission->create()->setData($data)->save();
        }
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
