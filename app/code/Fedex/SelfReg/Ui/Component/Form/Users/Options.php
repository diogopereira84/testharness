<?php

namespace Fedex\SelfReg\Ui\Component\Form\Users;

use Fedex\SelfReg\Model\ResourceModel\UserGroups\CollectionFactory;
use Magento\Customer\Model\Customer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;

class Options implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var array
     */
    protected $customerData;

    protected $collection;

    /**
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param Customer $customer
     * @param ResourceConnection $resource
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        protected RequestInterface $request,
        protected Customer $customer,
        protected ResourceConnection $resource
    ) {
        $this->collection = $collectionFactory->create();
    }
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $id = $this->request->getParam('id');
        if ($id && $this->customerData === null) {
            $this->collection->getSelect()->reset(\Zend_Db_Select::WHERE)->joinLeft(
                ['permissions' => $this->collection->getTable('user_groups_permission')],
                'main_table.id = permissions.group_id',
                ['company_id as site']
            )->where('main_table.group_type = ?', 'order_approval')
            ->where('main_table.id = ?', $id)
            ->group('main_table.id');
            if($this->collection->getSize()){
                $items = $this->collection->getItems();
                foreach ($items as $item) {
                    $companyId = $item->getSite();
                }
                $connection = $this->resource->getConnection();
                $companyAdvancedTable = $connection->getTableName('company_advanced_customer_entity');
                $customerCollection = $this->customer->getCollection();
                $customerCollection->addAttributeToSelect("*");
                $customerCollection->getSelect()->join($companyAdvancedTable . ' as ad_customer', 'e.entity_id = ad_customer.customer_id AND ad_customer.company_id = ' . $companyId, array('*'));
                foreach ($customerCollection as $customer) {
                    $this->customerData[] = [
                        'value'   => $customer->getId(),
                        'label' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    ];
                }
            }
        } else {
            $this->customerData[] = [
                'value'   => '',
                'label' => '',
            ];
        }

        return $this->customerData;
    }
}
