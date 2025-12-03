<?php
/**
 * @copyright Copyright (c) 2021 Fedex.
 * @author    Renjith Raveendran <renjith.raveendran.osv@fedex.com>
 */
namespace Fedex\OKTA\Model\UserRole;

use Magento\Authorization\Model\RoleFactory;
use Magento\Authorization\Model\RulesFactory;
use Magento\Authorization\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class RoleHandler
{
    private const ROLE_IDENTIFIER = 'role_id';
    private const CONGIG_KEY = 'fedex_okta/magento_roles';
    private const CONGIG_KEY_NEW_ROLES = 'fedex_okta/magento_new_roles';
    private const BACKEND_ROLE_PATH = 'fedex_okta/backend/roles';
    private const SUPERADMIN_ROLE = 'Super_Admin';
    private const PRODUCT_ROLE = 'Product';
    private const SUPER_ADMIN_IMAGE_ID = 'fxo_ecommerce_fxs_admin_test_app3537131';
    private const SUPER_ADMIN_ID = 1;
    public const  MARKETING_LIMITED_WRITE_NODE= 'Marketing_Limited_Write';
    public const  CTC_SUPER_USER_NODE= 'CTC_Super_User';

    public function __construct(
        public RoleFactory $roleFactory,
        public RulesFactory $rulesFactory,
        public RoleCollectionFactory $roleCollectionFactory,
        public ScopeConfigInterface $scopeConfig,
        public ConfigWriter $configWriter,
        public SerializerInterface $serializer,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @return bool
     */
    public function processRole(): bool
    {
        if (! empty($magentoRoles = $this->getRoleConfiguration())) {
            $this->saveRoleHandler($magentoRoles);
        }

        return true;
    }

    /**
     * @return bool
     */
    public function processNewAfterDeleteRole(): bool
    {
        if (! empty($magentoRoles = $this->getNewRoleConfiguration())) {
            $newMagentoRoles = [];
            $newMagentoRoles[static::MARKETING_LIMITED_WRITE_NODE] = $magentoRoles[static::MARKETING_LIMITED_WRITE_NODE];
            $newMagentoRoles[static::CTC_SUPER_USER_NODE] = $magentoRoles[static::CTC_SUPER_USER_NODE];
            $roleArray = $this->roleArrayProcess($newMagentoRoles);
            if (! empty($roleArray)) {
                $this->saveOktaBackendRoles($roleArray);
            }
        }
        return true;
    }

    /**
     * @return bool
     */
    public function optimizeNewRoles(): bool
    {
        if (! empty($magentoRoles = $this->getNewRoleConfiguration())) {
            /**
             * delete all roles
             */
            foreach ($magentoRoles as $roleName=>$role)
            {
                $roleName = str_replace("_", " ", trim($roleName));
                $this->deleteRoleFromName(trim($roleName));
            }
            $this->saveRoleHandler($magentoRoles);
        }
        return true;
    }

    /**
     * @param $magentoRoles
     * @return bool
     */
    public function saveRoleHandler($magentoRoles): bool
    {
        $roleArray = [];
        if ($adminRoleId = $this->getRoleId('Administrators')->getData(self::ROLE_IDENTIFIER)) {
            $roleArray[$this->getKeyWithTime($adminRoleId)] = $this->mapExternalRole($adminRoleId, trim($magentoRoles[self::SUPERADMIN_ROLE]['role']));
        }
        $newRoles = $this->roleArrayProcess($magentoRoles);
        $newRoleArray = array_merge_recursive($roleArray,$newRoles);
        if (! empty($newRoleArray)) {
            $this->saveOktaBackendRoles($newRoleArray);
        }
        return true;
    }

    /**
     * @return bool
     */
    public function processNewRole(): bool
    {
        if (! empty($magentoRoles = $this->getNewRoleConfiguration())) {
            $roleArray = [];

            /**
             * Get existing roles config
             */
            if (! empty($getExistingRolesConfig = $this->getExistingOktaBackendRoles())) {
                foreach($getExistingRolesConfig as $roleId => $role){
                    if($role['internal_role'] == static::SUPER_ADMIN_ID){
                        $getExistingRolesConfig[$roleId]['external_group'] =static::SUPER_ADMIN_IMAGE_ID;
                    }
                }
            }

            $roleArray = $this->roleArrayProcess($magentoRoles);
            /**
             * Merge new roles to existing roles
             */
            $newRoleArray = array_merge_recursive($getExistingRolesConfig,$roleArray);

            if (! empty($newRoleArray)) {
                $this->saveOktaBackendRoles($newRoleArray);
            }
        }
        return true;
    }



    /**
     * @param $magentoRoles
     * @return array
     */
    private function roleArrayProcess($magentoRoles): array
    {
        $roleArray = [];
        foreach ($magentoRoles as $role => $magentoRole) {
            if ($role == self::SUPERADMIN_ROLE) {
                continue;
            }
            $roleName = str_replace("_", " ", trim($role));
            $resource = explode(",", trim($magentoRole['resources']));
            if ($roleId = $this->saveRole(false, $roleName, $resource)) {
                $roleArray[$this->getKeyWithTime($roleId)] = $this->mapExternalRole($roleId, $magentoRole['role']);
            }
        }
        return $roleArray;
    }


    /**
     * @param $rid
     * @return mixed
     */
    protected function initRole($rid)
    {
        $role = $this->roleFactory->create()->load($rid);
        // preventing edit of relation role
        if ($role->getId() && $role->getRoleType() != \Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE) {
            $role->unsetData($role->getIdFieldName());
        }

        return $role;
    }

    /**
     * @param $rid
     * @param $roleName
     * @param $resource
     * @return bool
     */
    protected function saveRole($rid, $roleName, $resource)
    {
        try {
            $role = $this->initRole($rid);
            $role->setName($roleName)
                ->setPid(false)
                ->setRoleType(\Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE)
                ->setUserType(\Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN);
            $role->save();
            $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resource)->saveRel();
            return $role->getId();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' OKTA Error: '. $e->getMessage());
        }
        return true;
    }

    /**
     * @return mixed
     */
    protected function getRoleConfiguration()
    {
        return $this->scopeConfig->getValue(self::CONGIG_KEY, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    protected function getNewRoleConfiguration()
    {
        return $this->scopeConfig->getValue(self::CONGIG_KEY_NEW_ROLES, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }


    /**
     * @param $roleId
     * @param $fedexRole
     * @return array
     */
    protected function mapExternalRole($roleId, $fedexRole)
    {
        $data  = [];
        $data['internal_role'] = $roleId;
        $data['external_group'] = trim($fedexRole);
        return $data;
    }

    /**
     * @return string
     */
    private function getKeyWithTime($param = null)
    {
        $time = time() . $param;
        return '_' . $time . '_' . substr($time, -3);
    }

    /**
     * @param $roleArray
     */
    private function saveOktaBackendRoles($roleArray)
    {
        $this->configWriter->save(self::BACKEND_ROLE_PATH, $this->serializer->serialize($roleArray));
    }

    /**
     * Get existing roles
     */
    private function getExistingOktaBackendRoles()
    {
        if($this->scopeConfig->getValue(self::BACKEND_ROLE_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)) {
            return $this->serializer->unserialize($this->scopeConfig->getValue(
                self::BACKEND_ROLE_PATH,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ));
        }

        return [];
    }

    /**
     * Creates role collection
     *
     * @return \Magento\Authorization\Model\ResourceModel\Role\Collection
     */
    public function createRoleCollection()
    {
        return $this->roleCollectionFactory->create();
    }

    /**
     * @return false|\Magento\Framework\DataObject
     */
    public function getRoleId($roleName = 'Administrators')
    {
        $roleCollection = $this->createRoleCollection()
            ->addFieldToFilter('parent_id', 0)
            ->addFieldToFilter('tree_level', 1)
            ->addFieldToFilter('role_type', \Magento\Authorization\Model\Acl\Role\Group::ROLE_TYPE)
            ->addFieldToFilter('user_id', 0)
            ->addFieldToFilter('user_type', \Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN)
            ->addFieldToFilter('role_name', $roleName);
        $roleCollection->getSelect()->limit(1);
        if ($roleCollection->count()) {
            return $roleCollection->getFirstItem();
        }
        return false;
    }

    private function getProductRolePermissions(): array
    {
        $magentoRoles = $this->getRoleConfiguration();
        return explode(",", trim($magentoRoles[self::PRODUCT_ROLE]['resources']));
    }

    public function updateProductRolePermission(): bool
    {
        $roleName = 'Product';
        if ($productRoleId = $this->getRoleId($roleName)->getData(self::ROLE_IDENTIFIER)) {
            $this->saveRole($productRoleId,$roleName, $this->getProductRolePermissions());
            return true;
        }
        return false;
    }
    public function deleteRoleFromName($roleName = '')
    {
        if( ! $roleName ){
            return false;
        }
        if(! $roleData = $this->getRoleId($roleName)){
            return false;
        }

        $roleId = $roleData->getData(self::ROLE_IDENTIFIER);

        $role = $this->roleFactory->create()->load($roleId);
        if (!$role->getId()) {
            return false;
        }
        try {
            $role->delete();
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.' An error occurred while deleting this role : ' . $e->getMessage() );
        }
    }
}
