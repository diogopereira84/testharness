<?php

declare (strict_types = 1);

namespace Fedex\SelfReg\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ParentUserGroup extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        $this->_init('parent_customer_group', 'entity_id');
    }

    public function addCategoryToCustomerGroup($customerGroupId, $categoryId)
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable('parent_customer_group');

        $select = $connection->select()
            ->from($tableName)
            ->where('customer_group_id = ?', $customerGroupId);

        $row = $connection->fetchRow($select);

        if ($row) {
            // Already exists: update category_ids field
            $current = $row['category_ids'] ?? '';
            $categories = $current ? explode(',', $current) : [];
            if (!in_array($categoryId, $categories)) {
                $categories[] = $categoryId;
                $categories = array_unique($categories);
                $connection->update(
                    $tableName,
                    ['category_ids' => implode(',', $categories)],
                    ['customer_group_id = ?' => $customerGroupId]
                );
            }
        } else {
            $connection->insert($tableName, [
                'customer_group_id' => $customerGroupId,
                'category_ids' => $categoryId
            ]);
        }
    }

    public function removeCategoryFromCustomerGroup($customerGroupId, $categoryId)
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable('parent_customer_group');
        $select = $connection->select()
            ->from($tableName)
            ->where('customer_group_id = ?', $customerGroupId);

        $row = $connection->fetchRow($select);

        if ($row && $row['category_ids']) {
            $categories = explode(',', $row['category_ids']);
            $categories = array_diff($categories, [$categoryId]);
            $categoryString = implode(',', $categories);

            if ($categoryString) {
                $connection->update(
                    $tableName,
                    ['category_ids' => $categoryString],
                    ['customer_group_id = ?' => $customerGroupId]
                );
            } else {
                // No categories left, set to null or empty string
                $connection->update(
                    $tableName,
                    ['category_ids' => ''],
                    ['customer_group_id = ?' => $customerGroupId]
                );
            }
        }
    }
}
