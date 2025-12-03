<?php

/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Model;

use Exception;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\Api\FilterBuilder;
use Magento\Tax\Api\TaxClassManagementInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Framework\Stdlib\DateTime as DateTimeFormatter;
use Magento\Customer\Model\GroupFactory;
use DateTime;

class CustomerGroupSaveModel extends AbstractModel
{
    private const TABLE_NAME = 'parent_customer_group';

    /**
     * CustomerGroupSaveModel class constructor
     *
     * @param GroupInterfaceFactory $groupInterfaceFactory
     * @param GroupRepositoryInterface $groupRepositoryInterface
     * @param CompanyRepositoryInterface $companyRepositoryInterface
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     * @param Session $session
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param GroupFactory $customerGroupFactory
     */
    public function __construct(
        private GroupInterfaceFactory $groupInterfaceFactory,
        private GroupRepositoryInterface $groupRepositoryInterface,
        private CompanyRepositoryInterface $companyRepositoryInterface,
        private ResourceConnection $resourceConnection,
        private LoggerInterface $logger,
        private Session $session,
        private TaxClassRepositoryInterface $taxClassRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private FilterBuilder $filterBuilder,
        private GroupFactory $customerGroupFactory
    ) {
    }

    /**
     * Save new group in customer group and parent customer group table
     *
     * @param string $baseGroupName
     * @param string $groupName
     * @param int $parentGroupId
     *
     * @return ?int
     */
    public function saveInCustomerGroup(string $baseGroupName, string $groupName, int $parentGroupId): ?int
    {
        $customerGroupId = null;

        try {
            if (!$this->isBaseNameExisting($baseGroupName, $parentGroupId)) {
                $group = $this->groupInterfaceFactory->create();
                $taxClassId = $this->getTaxClassId();
                $group->setCode($groupName);
                $group->setTaxClassId($taxClassId);

                $groupSaved = $this->groupRepositoryInterface->save($group);
                $customerGroupId = (int) $groupSaved->getId();

                $this->saveInParentCustomerGroup($customerGroupId, $parentGroupId);
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . 'An error occurred while saving the customer group: ' . $e->getMessage()
            );
        }

        return $customerGroupId;
    }

    /**
     * Save in parent customer group
     *
     * @param int $newCustomerGroupId
     * @param int $parentGroupId
     * @return void
     */
    public function saveInParentCustomerGroup($newCustomerGroupId, $parentGroupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);
        
        $data = [
            'customer_group_id' => $newCustomerGroupId,
            'parent_group_id' => $parentGroupId,
            'created_at' => (new DateTime())->format(DateTimeFormatter::DATETIME_PHP_FORMAT),
        ];

        try {
            $connection->insert($tableName, $data);
        } catch (\Exception $e) {
            $this->logger->error(__METHOD__ . ':' . __LINE__ .$e->getMessage());
        }
    }

    /**
     * Get tax class id from tax class table
     *
     * @return int
     */
    public function getTaxClassId()
    {
        $filter = $this->filterBuilder->setField(ClassModel::KEY_TYPE)
                ->setValue(TaxClassManagementInterface::TYPE_CUSTOMER)
                ->create();
        $searchCriteria = $this->searchCriteriaBuilder->addFilters([$filter])->create();
        $searchResults = $this->taxClassRepository->getList($searchCriteria);

        $items = $searchResults->getItems();
        $firstItem = reset($items);

        $taxId = $firstItem ? $firstItem->getClassId() : 0;

        return $taxId;
    }

    /**
     * Edit existing customer group
     *
     * @param string $baseGroupName
     * @param string $groupName
     * @param int $parentGroupId
     * @param int $formGroupId
     *
     * @return ?int
     */
    public function editCustomerGroup(
        string $baseGroupName,
        string $groupName,
        int $parentGroupId,
        int $formGroupId
    ): ?int {
        $customerGroupId = null;

        try {
            $customerGroup = $this->groupRepositoryInterface->getById($formGroupId);
            $oldName = $customerGroup->getCode();
            if ($oldName !== $groupName) {
                if (!$this->isBaseNameExisting($baseGroupName, $parentGroupId)) {
                    $customerGroup->setCode($groupName);
                    $this->groupRepositoryInterface->save($customerGroup);
                    $customerGroupId = (int) $customerGroup->getId();
                }
            } else {
                $customerGroupId = (int) $customerGroup->getId();
            }
        } catch (Exception $e) {
            $this->logger->error(
                __METHOD__ . ':' . __LINE__ . ' An error occurred while editing the customer group: ' . $e->getMessage()
            );
        }

        return $customerGroupId;
    }

    /**
     * Check if base name already exists
     *
     * @param string $baseGroupName
     * @param int $parentGroupId
     *
     * @return bool
     */
    public function isBaseNameExisting(string $baseGroupName, int $parentGroupId): bool
    {
        $adminCustomerGroupCollection = $this->customerGroupFactory->create()->getCollection();
        $adminCustomerGroupCollection->getSelect()->joinInner(
            ['pcg' => $adminCustomerGroupCollection->getTable('parent_customer_group')],
            'main_table.customer_group_id = pcg.customer_group_id',
            []
        )->where('customer_group_code = ?', $baseGroupName)
        ->where('parent_group_id = ?', $parentGroupId);

        return $adminCustomerGroupCollection->getSize() > 0;
    }
}
