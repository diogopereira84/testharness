<?php

namespace Fedex\FXOCMConfigurator\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\State;
use Fedex\FXOCMConfigurator\Model\UserworkspaceFactory;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory as UserworkspaceCollectionFactory;
use Magento\Customer\Model\SessionFactory;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Model\Session;
use Psr\Log\LoggerInterface;
use Fedex\Base\Helper\Auth as AuthHelper;

class Batchupload extends AbstractHelper
{

    protected $context;
    protected $userworkspaceCollectionFactory;

    /**
     * Constructor
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptorInterface
     * @param State $state
     * @param UserworkspaceFactory $userworkspace
     * @param SessionFactory $customerSessionFactory
     * @param UserworkspaceCollectionFactory $UserworkspaceCollectionFactory
     * @param SsoConfiguration $ssoConfiguration
     * @param SdeHelper $sdeHelper
     * @param SelfReg $selfRegHelper
     * @param DeliveryHelper $deliveryHelper
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param AuthHelper $authHelper
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        ScopeConfigInterface $scopeConfig,
        protected EncryptorInterface $encryptorInterface,
        protected State $state,
        protected UserworkspaceFactory $userworkspace,
        protected SessionFactory $customerSessionFactory,
        UserworkspaceCollectionFactory $UserworkspaceCollectionFactory,
        protected SsoConfiguration $ssoConfiguration,
        protected SdeHelper $sdeHelper,
        protected SelfReg $selfRegHelper,
        protected DeliveryHelper $deliveryHelper,
        protected Session $customerSession,
        protected LoggerInterface $logger,
        protected AuthHelper $authHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->userworkspaceCollectionFactory = $UserworkspaceCollectionFactory;
        parent::__construct($context);
    }

    /*
    * Add Data in batch upload
    */
    public function addBatchUploadData($workSpaceData)
    {
        if (empty($workSpaceData)) {
            return;
        }
        $this->addDataInSession($workSpaceData);

        $customerId = $this->customerId();

        $applicationType = $this->getApplicationType();
        $oldUploadDate = $this->getOldUploadedDate($workSpaceData);

        if ($customerId) {
            $userworkspaceCollection = $this->getUserWorkspaceForCustomer(
                $customerId
            );
            $userworkspaceModel = $this->userworkspace->create();

            if ($userworkspaceCollection->getSize()) {
                $userworkspaceId = $userworkspaceCollection
                    ->getFirstItem()
                    ->getData("userworkspace_id");
                $checkProjectsExists = $this->checkProjectsExists(
                    $workSpaceData
                );

                if ($checkProjectsExists) {
                    $this->deleteBatchUploadRow($userworkspaceId);
                } else {
                    //Update Data
                    $userworkspaceModel->load(
                        $userworkspaceId,
                        "userworkspace_id"
                    );
                    $userworkspaceModel->setWorkspaceData($workSpaceData);
                    $userworkspaceModel->setApplicationType($applicationType);
                    $userworkspaceModel->setOldUploadDate($oldUploadDate);
                    $userworkspaceModel->save();
                }
            } else {
                //Insert Data
                $userworkspaceModel->setCustomerId($customerId);
                $userworkspaceModel->setWorkspaceData($workSpaceData);
                $userworkspaceModel->setApplicationType($applicationType);
                $userworkspaceModel->setOldUploadDate($oldUploadDate);
                $userworkspaceModel->save();
            }
        }
    }

    /*
    * Chekc if project exists
    */
    public function checkProjectsExists($workSpaceData)
    {
        $workSpaceDataNew = json_decode($workSpaceData, true);
        if (!empty($workSpaceDataNew["projects"])) {
            foreach ($workSpaceDataNew["projects"] as $project) {
                if (!empty($project["product"])) {
                    return false;
                }
            }
        }
        return true;
    }

    /*
     * Function to create customer session to save worspace data
     */
    public function addDataInSession($workSpaceData)
    {
        $this->customerSession->setUserworkspace($workSpaceData);
    }

    /*
     * Fetch value of workspace Data from customer session.
     */
    public function getUserWorkspaceSessionValue()
    {
        if ($this->customerSession->getUserworkspace()) {
            return $this->customerSession->getUserworkspace();
        }
        return null;
    }

    /*
    * Delete batch upload data from workspace
    */
    public function deleteBatchUploadRow($userworkspaceId)
    {
        $userworkspaceModel = $this->userworkspace->create();
        $userworkspaceModel->load($userworkspaceId, "userworkspace_id");
        $userworkspaceModel->delete();
    }

    /*
    * Get old Upload Date from workspace and file
    */
    public function getOldUploadedDate($workSpaceData)
    {
        $workSpaceData = json_decode($workSpaceData, true);
        $uploadDateTime = [];
        if (!empty($workSpaceData["files"])) {
            foreach ($workSpaceData["files"] as $files) {
                if (isset($files["uploadDateTime"])) {
                    $uploadDateTime[] = $files["uploadDateTime"];
                }
            }
            usort($uploadDateTime, function ($a, $b) {
                return strtotime($a) - strtotime($b);
            });
            if (is_array($uploadDateTime) && count($uploadDateTime)) {
                return $uploadDateTime[0];
            }
        }
    }

    /*
    * Get customer id
    */
    public function customerId()
    {
        $customerId = "";
        $customer = $this->customerSessionFactory->create();
        if ($this->authHelper->isLoggedIn()) {
            $customerId = $customer->getId();
        }
        return $customerId;
    }

    /*
    * Get Application Type
    */
    public function getApplicationType()
    {
        if ($this->ssoConfiguration->isRetail()) {
            return "retail";
        }

        if ($this->sdeHelper->getIsSdeStore()) {
            return "SDE";
        }

        if ($this->selfRegHelper->isSelfRegCustomer()) {
            return "selfreg";
        }

        if ($this->deliveryHelper->isEproCustomer()) {
            return "epro";
        }
    }

    /*
     * Get userworkspace value on the basis of customerId
     */
    function getUserworkSpaceFromCustomerId($customerId)
    {
        $userworkspaceCollection = $this->userworkspaceCollectionFactory->create();
        $userworkspaceCollection->addFieldToFilter("customer_id", $customerId);
        return $userworkspaceCollection
            ->getFirstItem()
            ->getData("workspace_data");
    }

    /**
     * Get WorkSpace Days for delete
     */
    public function getWorkSpaceDeleteDays()
    {
        return $this->scopeConfig->getValue(
            "fedex/workspace_project_setting/delete_project_days_no"
        );
    }

    /**
     * Get Retail Print Url
     */
    public function getRetailPrintUrl()
    {
        return $this->scopeConfig->getValue(
            "allprintproducts/general/retail_print_product_url"
        );
    }

    /**
     * Get Commercial Print Url
     */
    public function getCommercialPrintUrl()
    {
        return $this->scopeConfig->getValue(
            "allprintproducts/general/commercial_print_product_url"
        );
    }

    /**
     * Update user workspace session after login
     */
    public function updateUserworkspaceDataAfterLogin($customerId)
    {
        $mergedUserworkspace = "{}";
        // Get current userworksapce seession value
        $currentSessionValue = $this->customerSession->getUserworkspace();

        // load the userworkspace collection for customerid
        $userworkspaceCollection = $this->getUserWorkspaceForCustomer(
            $customerId
        );
        if ($userworkspaceCollection->getSize()) {
            // Get user workspace data for the customer
            $customerUserWorksapce = $userworkspaceCollection
                ->getFirstItem()
                ->getData("workspace_data");

            $currentSessionValue = empty($currentSessionValue)
                ? "{}"
                : $currentSessionValue;
            $customerUserWorksapce = empty($customerUserWorksapce)
                ? "{}"
                : $customerUserWorksapce;
            $currentValue = json_decode($currentSessionValue, true);
            $customerValue = json_decode($customerUserWorksapce, true);

            if (is_array($currentValue) && is_array($customerValue)) {
                $mergedUserworkspace = json_encode(
                    array_merge_recursive($currentValue, $customerValue)
                );
            }
        } else {
            $mergedUserworkspace = $currentSessionValue;
        }

        // add the value in db and session
        $this->addBatchUploadData($mergedUserworkspace);
    }

    /**
     * Get user workspace session from customer id
     */
    public function getUserWorkspaceForCustomer($customerId)
    {
        $userworkspaceCollection = $this->userworkspaceCollectionFactory->create();
        return $userworkspaceCollection->addFieldToFilter(
            "customer_id",
            $customerId
        );
    }
}
