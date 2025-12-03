<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Model;

use Exception;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * B-1250149 : Magento Admin UI changes to group all the Customer account details
 */
class CompanyData extends AbstractModel
{
    /**
     * CompanyData constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param AdditionalDataFactory $additionalDataFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyManagementInterface $companyManagement
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param ToggleConfig $toggleConfig
     * @param ConfigInterface $configInterface
     * @param OndemandConfigInterface $ondemandConfigInterface
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        protected Context                              $context,
        protected Registry                             $registry,
        protected AdditionalDataFactory      $additionalDataFactory,
        protected CompanyRepositoryInterface $companyRepository,
        protected CompanyManagementInterface $companyManagement,
        protected SearchCriteriaBuilder      $searchCriteriaBuilder,
        protected FilterBuilder              $filterBuilder,
        protected ToggleConfig               $toggleConfig,
        protected ConfigInterface            $configInterface,
        protected OndemandConfigInterface    $ondemandConfigInterface,
        protected ?AbstractResource          $resource = null,
        protected ?AbstractDb                $resourceCollection = null,
        array                                $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Get company additional data
     *
     * @param int $companyId
     * @return array
     */
    public function getAdditionalData($companyId)
    {
        try {
            $additionalData = $this->additionalDataFactory->create()
                ->getCollection()
                ->addFieldToSelect('*')
                ->addFieldToFilter(AdditionalData::COMPANY_ID, ['eq' => $companyId])
                ->getFirstItem();
            if ($additionalData) {
                return $additionalData->getData();
            }
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Get customer company id by customer group
     *
     * @param int $customerGroupId
     * @return int|null
     */
    public function getCompanyIdByCustomerGroup($customerGroupId)
    {
        try {
            $this->searchCriteriaBuilder->addFilters(
                [
                    $this->filterBuilder->setField('main_table.customer_group_id')
                        ->setConditionType('eq')
                        ->setValue($customerGroupId)
                        ->create(),
                ]
            );

            $searchCriteria = $this->searchCriteriaBuilder->create();
            $searchCriteria->setPageSize(1)->setCurrentPage(1);
            $companies = $this->companyRepository->getList($searchCriteria)->getItems();
            if (!empty($companies)) {
                $company = current($companies);
                return $company->getId();
            }
        } catch (Exception $e) {
            $this->_logger->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get customer store view id by customer group
     *
     * @param int $customerGroupId
     * @return int|null
     * B-1598912
     */
    public function getStoreViewIdByCustomerGroup($customerGroupId)
    {
        $storeViewId = null;
        if ($customerGroupId && $companyId = $this->getCompanyIdByCustomerGroup($customerGroupId)) {
            $storeViewId = $this->ondemandConfigInterface->getB2bDefaultStore();
        }

        return $storeViewId;
    }

    public function getByCustomerId($customerId)
    {
        return $this->companyManagement->getByCustomerId($customerId);
    }
}
