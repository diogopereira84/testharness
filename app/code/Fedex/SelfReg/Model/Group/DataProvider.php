<?php
namespace Fedex\SelfReg\Model\Group;

use Fedex\SelfReg\Model\ResourceModel\UserGroups\CollectionFactory;
use Magento\Framework\App\RequestInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        protected RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $this->loadedData = [];
        $id = $this->request->getParam('id');
        if ($id) {
            $this->collection->getSelect()->reset(\Zend_Db_Select::WHERE)->join(
                ['ugp' => $this->collection->getTable('user_groups_permission')],
                'main_table.id = ugp.group_id',
                ['company_id as site']
            )->joinLeft(
                ['user_entity' => $this->collection->getTable('customer_entity')],
                'user_entity.entity_id = ugp.user_id',
                []
            )->joinLeft(
                ['approver_entity' => $this->collection->getTable('customer_entity')],
                'FIND_IN_SET(`approver_entity`.`entity_id`, `ugp`.`order_approval`) > 0',
                []
            )->columns([
                'order_approvers' => new \Zend_Db_Expr(
                        "GROUP_CONCAT(DISTINCT CONCAT(approver_entity.entity_id) ORDER BY approver_entity.entity_id SEPARATOR ',')"
                ),
                'users' => new \Zend_Db_Expr(
                    "GROUP_CONCAT(DISTINCT CONCAT(user_entity.entity_id) ORDER BY user_entity.entity_id SEPARATOR ',')"
                ),
            ])
           ->where('main_table.group_type = ?', 'order_approval')
           ->where('main_table.id = ?', $id)
           ->group(['main_table.id', 'main_table.group_name']);
            $items = $this->collection->getItems();
            foreach ($items as $itemData) {
                $orderApprovers = explode(',', $itemData->getOrderApprovers() ?? '');
                $users = explode(',', $itemData->getUsers() ?? '');
                $this->loadedData[$itemData->getData('id')]['id'] = $itemData->getId();
                $this->loadedData[$itemData->getData('id')]['site'] = $itemData->getSite();
                $this->loadedData[$itemData->getData('id')]['group_name'] = $itemData->getGroupName();
                $this->loadedData[$itemData->getData('id')]['order_approver'] =  $orderApprovers;
                $this->loadedData[$itemData->getData('id')]['users'] = $users;
            }
        }
        return $this->loadedData;
    }
}