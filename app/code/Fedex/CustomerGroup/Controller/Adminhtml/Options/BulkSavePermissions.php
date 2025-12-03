<?php
/**
 * Copyright ©FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fedex\CustomerGroup\Controller\Adminhtml\Options;

use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\SelfReg\Model\EnhanceUserRolesFactory;
use Magento\Backend\App\Action\Context;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Exception;

class BulkSavePermissions extends \Magento\Backend\App\Action
{
    public const MANAGE_CATALOG_PERMISSION = 'manage_catalog';
    public const REVIEW_ORDERS_PERMISSION = 'review_orders';
    public const ENHANCED_USER_ROLES_TABLE_NAME = 'enhanced_user_roles';
    public const RESPONSE_STATUS = 'status';
    public const RESPONSE_MESSAGE = 'message';

    /**
     * BulkSavePermissions constructor
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Http $request
     * @param LoggerInterface $logger
     * @param CustomerRepository $customerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerFactory $customerFactory
     * @param CompanyManagementInterface $companyManagement
     * @param AdminConfigHelper $adminConfigHelper
     * @param EnhanceUserRolesFactory $enhancedUserRolesFactory
     * @param ResourceConnection $resourceConnection
     * @param CompanyRepositoryInterface $companyRepository
     * @param AdditionalDataFactory $additionalDataFactory
     */
    public function __construct(
        Context $context,
        protected JsonFactory $resultJsonFactory,
        private Http $request,
        private LoggerInterface $logger,
        private CustomerRepository $customerRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private CustomerFactory $customerFactory,
        private CompanyManagementInterface $companyManagement,
        private AdminConfigHelper $adminConfigHelper,
        private EnhanceUserRolesFactory $enhancedUserRolesFactory,
        private ResourceConnection $resourceConnection,
        private CompanyRepositoryInterface $companyRepository,
        private AdditionalDataFactory $additionalDataFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Bulk save permissions for selected users
     */
    public function execute(): Json
    {
        $resultJsonData = $this->resultJsonFactory->create();
        $requestData = $this->request->getParams();

        try {
            if (empty($requestData)) {
                return $this->createErrorResponse($resultJsonData, 'Error bulk saving permissions for users');
            }

            $selectedCustomerIds = $requestData['selectedIds'] ?? null;
            $selectedPermissions = $requestData['selectedPermissions'] ?? null;
            if (!$selectedCustomerIds) {
                return $this->createErrorResponse(
                    $resultJsonData,
                    'No users were found when trying to bulk save permissions.'
                );
            }

            $savedCustomersCount = 0;
            $savePermissionsData = [];
            $redirectUrl = '';
            $isReviewOrdersAllowedGlobally = (bool) $this->adminConfigHelper->isOrderApprovalB2bGloballyEnabled();
            $selectedCustomerData = $this->getSelectedCustomers($selectedCustomerIds);
            $selectedCompanies = array_column($selectedCustomerData, 'company_id');
            $selectedCompanyData = $this->getCompanyPermissionOptions(
                $selectedCompanies,
                $isReviewOrdersAllowedGlobally
            );

            foreach ($selectedCustomerData as $customerData) {
                $selectedCompanyId = $customerData['company_id'];
                $savePermissionsData = $this->determineSelectedPermissions(
                    $customerData['id'],
                    $selectedCompanyId,
                    $selectedCompanyData[$selectedCompanyId]['isManageCatalogAllowed'],
                    $selectedCompanyData[$selectedCompanyId]['isReviewOrdersAllowed'],
                    $selectedPermissions,
                    $savePermissionsData
                );
            }

            if ($savePermissionsData) {
                $savedCustomersCount = count($savePermissionsData);
                $savePermissionsData = array_merge(...$savePermissionsData);
                if ($savePermissionsData) {
                    $this->saveSelectedPermissions($savePermissionsData);
                }
            }
            if ($savedCustomersCount && $this->messageManager) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 record(s) were updated.', $savedCustomersCount)
                );
            }
            $redirectUrl = $this->getUrl('customer/index/index');
            $response = $resultJsonData->setData(
                ['status' => 'success', 'redirect' => $redirectUrl]
            );
        } catch (Exception $e) {
            $this->logger->critical(
                __METHOD__ . ':' . __LINE__ . ' Error bulk saving permissions for users: ' .
                $e->getMessage()
            );
            return $this->createErrorResponse($resultJsonData, $e->getMessage());
        }

        return $response;
    }

    /**
     * Determine selected permissions for a customer based on their company
     *
     * @param int $customerId
     * @param int $companyId
     * @param bool $isManageCatalogAllowed
     * @param bool $isReviewOrdersAllowed
     * @param array $selectedPermissions
     * @param array $savePermissionsData
     *
     * @return array
     */
    private function determineSelectedPermissions(
        int $customerId,
        int $companyId,
        bool $isManageCatalogAllowed,
        bool $isReviewOrdersAllowed,
        array $selectedPermissions,
        array $savePermissionsData
    ): array {
        if (!$isManageCatalogAllowed) {
            if (isset($selectedPermissions[self::MANAGE_CATALOG_PERMISSION])) {
                unset($selectedPermissions[self::MANAGE_CATALOG_PERMISSION]);
            }
        }
        if (!$isReviewOrdersAllowed) {
            if (isset($selectedPermissions[self::REVIEW_ORDERS_PERMISSION])) {
                unset($selectedPermissions[self::REVIEW_ORDERS_PERMISSION]);
            }
        }
        if ($selectedPermissions) {
            $savePermissionsData[] = array_map(function ($value) use ($companyId, $customerId) {
                return [
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'permission_id' => $value
                ];
            }, $selectedPermissions, []);
        }

        return $savePermissionsData;
    }

    /**
     * Save selected permissions for selected customers
     *
     * @param array $savePermissionsData
     *
     * @return void
     */
    private function saveSelectedPermissions(array $savePermissionsData): void
    {
        $connection  = $this->resourceConnection->getConnection();
        $tableName = self::ENHANCED_USER_ROLES_TABLE_NAME;

        $connection->insertOnDuplicate($tableName, $savePermissionsData);
    }

    /**
     * Create an error response
     *
     * @param Json $resultJsonData
     * @param string $message
     *
     * @return Json
     */
    private function createErrorResponse(Json $resultJsonData, string $message): Json
    {
        return $resultJsonData->setData([
            self::RESPONSE_STATUS => 'error',
            self::RESPONSE_MESSAGE => $message,
        ]);
    }

    /**
     * Get selected customer objects
     *
     * @param array $selectedCustomerIds
     *
     * @return array
     */
    private function getSelectedCustomers(array $selectedCustomerIds): array
    {
        $selectedCustomerData = [];
        $customerSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $selectedCustomerIds, 'in')
            ->create();

        foreach ($this->customerRepository->getList($customerSearchCriteria)->getItems() as $customer) {
            if ($customer->getExtensionAttributes() !== null
                && $customer->getExtensionAttributes()->getCompanyAttributes() !== null
                && $customer->getExtensionAttributes()->getCompanyAttributes()->getCompanyId()
            ) {
                $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
                if ($companyAttributes->getCompanyId()) {
                    $selectedCustomerData[] = [
                        'id' => (int) $customer->getId(),
                        'company_id' => (int) $companyAttributes->getCompanyId()
                    ];
                }
            }
        };
        return $selectedCustomerData;
    }

    /**
     * Get Manage Catalog and Review Orders Permission Options for selected companies
     *
     * @param array $selectedCompanies
     * @param bool $isReviewOrdersAllowedGlobally
     *
     * @return array
     */
    private function getCompanyPermissionOptions(
        array $selectedCompanies,
        bool $isReviewOrdersAllowedGlobally
    ): array {
        $selectedCompanyData = [];

        $companySearchCriteria = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $selectedCompanies, 'in')
            ->create();
        foreach ($this->companyRepository->getList($companySearchCriteria)->getItems() as $company) {
            $selectedCompanyData[$company->getId()]['isManageCatalogAllowed'] =
                (bool) $company->getAllowSharedCatalog() ?? null;
        }

        $selectedCompaniesAdditionalData = $this->additionalDataFactory->create()->getCollection()
            ->addFieldToSelect(['company_id', 'is_b2b_order_approval_enabled'])
            ->addFieldToFilter('company_id', ['in' => $selectedCompanies])->getItems();
        foreach ($selectedCompaniesAdditionalData as $companyAdditionalData) {
            $selectedCompanyData[$companyAdditionalData->getCompanyId()]['isReviewOrdersAllowed'] =
                (bool) $companyAdditionalData->getIsApprovalWorkflowEnabled() ?? null &&
                $isReviewOrdersAllowedGlobally;
        }

        return $selectedCompanyData;
    }
}
