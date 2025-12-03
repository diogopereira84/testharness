<?php

namespace Fedex\SelfReg\Helper;

use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Fedex\SelfReg\Model\ResourceModel\EnhanceUserRoles\CollectionFactory as UserRoleCollectionFactory;
use Fedex\SelfReg\Model\ResourceModel\EnhanceRolePermission\CollectionFactory as RolePermissionCollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\SelfReg\Model\ResourceModel\UserGroupsPermission\CollectionFactory as UserGroupPermissionCollection;
use Fedex\Delivery\Helper\Data as DeliveryDataHelper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public const PERSONAL_ADDRESS_BOOK_COMMERCIAL = 'Personal Address Book';
    public const ADDRESS_BOOK_RETAIL = 'Address Book';
    public const ADDRESS_BOOK_RETAIL_ORDER = 400;
    public const ADDRESS_BOOK_COMMERCIAL_ORDER = 200;
    protected $roleUser;
    protected $rolePermissions;

    public function __construct(
        protected CommercialHelper $commercialHelper,
        protected SelfReg $selfReg,
        Context $context,
        protected DeliveryDataHelper $deliveryHelper,
        private EnhanceUserRolesFactory $userRoleFactory,
        private UserRoleCollectionFactory $roleCollectionFacotry,
        private RolePermissionCollectionFactory $rolePermissionCollectionFacotry,
        private LoggerInterface $logger,
        private CustomerRepositoryInterface $customerRepository,
        private UserGroupPermissionCollection $userGroupsPermissionCollection,
    )
    {
        parent::__construct($context);
    }
    public function getLabel()
    {
        
        $isSelfRegAdminUpdates =$this->commercialHelper->isSelfRegAdminUpdates();
        if($isSelfRegAdminUpdates)
        {
            $label = "Manage Users";
        }
        else{
            $label = "Company Users"; 
        }
        return $label;
    }

    /*
     * Function to get id of review order permission
    */
    public function getReviewOrderPermissionID()
    {
        $collection = $this->rolePermissionCollectionFacotry->create();
        $collection->addFieldToFilter('label', ['like' => '%::review_orders']);
        $collectionData = $collection->getData();
        return $collectionData[0]['id'];
    }

    /*
     * Function to set permission of user who are selected as order apporver
    */
    public function setPermissions($customerId, $companyId)
    {
        try {
            $permissionId = $this->getReviewOrderPermissionID();
            $valueCheck = $this->checkIfValueExist($customerId, $permissionId, $companyId);
            $customer = $this->checkCustomerIsExists($customerId);
            if ($valueCheck == 0 && is_object($customer)) {
                $this->userRoleFactory->create()
                ->setCompanyId($companyId)
                ->setCustomerId($customerId)
                ->setPermissionId($permissionId)
                ->save();
            } else {
                $this->logger->info(__METHOD__ . ':' . __LINE__ . 'Data already exist for Customer ID = '. $customerId .
                 ' company Id = ' . $companyId .
                 ' permission id = ' . $permissionId
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . $e->getMessage());
        }

    }

    /**
     * Delete permission
     */
    public function deletePermission($customerId,$companyId)
    {
        $permissionId = $this->getReviewOrderPermissionID();
        $collection = [];
        $collection =  $this->roleCollectionFacotry->create()
                        ->addFieldToFilter('customer_id',$customerId)
                        ->addFieldToFilter('company_id',$companyId)
                        ->addFieldToFilter('permission_id',$permissionId)->getFirstItem();
        if($collection->getData()) {
            $collection->delete();
        }
    }


    /**
     * check if the value to be insert already exist or not
     */
    public function checkIfValueExist($customerId,$permissionId,$companyId) {

        $collection =  $this->roleCollectionFacotry->create()
        ->addFieldToFilter('customer_id',$customerId)
        ->addFieldToFilter('company_id',$companyId)
        ->addFieldToFilter('permission_id',$permissionId)->getData();
       return count($collection);
    }

    /**
     * check if customer exist or not
     */
    public function checkCustomerIsExists($customerId){
        try {
           return $this->customerRepository->getById($customerId);
          } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Customer Order Approval Count
     */
    public function checkIfCustomerIsOrderApprovar($customerId) {
        $collection = $this->userGroupsPermissionCollection->create();
        $collection->addFieldToFilter('order_approval', array('finset' => $customerId));
        $collectionData = $collection->getData();
        return count($collectionData);
    }

   /**
     * To set label
     */
    public function getLabelNameForAddressBook()
    {
        if($this->deliveryHelper->isCommercialCustomer()) {
            return self::PERSONAL_ADDRESS_BOOK_COMMERCIAL;
        } else {
            return self::ADDRESS_BOOK_RETAIL;
        }
    }

   /**
     * To set sort order for Personal address book according to figma
     */
    public function getSortOrderForAddressBook()
    {
        if($this->deliveryHelper->isCommercialCustomer()) {
            return self::ADDRESS_BOOK_COMMERCIAL_ORDER;
        } else {
            return self::ADDRESS_BOOK_RETAIL_ORDER;
        }
    }
}
