<?php
namespace Fedex\CustomerGroup\Plugin\Adminhtml\Group;

use Magento\Customer\Controller\Adminhtml\Group\Save;
use Magento\Framework\App\ResourceConnection;

class SaveCustomerGroupPlugin
{
    const TABLE_NAME = 'customer_group';
    /**
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        protected ResourceConnection $resourceConnection
    )
    {
    }
    /**
     * After Execute
     *
     * @param Save $subject
     * @param array $result
     * @return array $result
     */
    public function afterExecute(Save $subject, $result)
    {
        $parentGroupId = (int)$subject->getRequest()->getParam('parent_group_code');
        $customerGroupCode = (string)$subject->getRequest()->getParam('code');
        if ($parentGroupId) {
            $this->updateParentGroupId($customerGroupCode, $parentGroupId);
        }
        return $result;
    }
    /**
     * Update values in the database
     *
     * @param string $customerGroupCode
     * @param int $parentGroupId
     */
    public function updateParentGroupId($customerGroupCode, $parentGroupId)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName(static::TABLE_NAME);
        $data = [
            'parent_group_id' => $parentGroupId
        ];
        $where = ['customer_group_code = ?' => $customerGroupCode];
        $connection->update($tableName, $data, $where);
    }
}
