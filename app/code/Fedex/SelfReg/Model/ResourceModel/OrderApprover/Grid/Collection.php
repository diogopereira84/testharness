<?php

namespace Fedex\SelfReg\Model\ResourceModel\OrderApprover\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\DB\Select;

class Collection extends SearchResult
{
    /**
     * Initialize select query
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        
        $connection = $this->getConnection();
        
        // Create subquery for approvers using Magento's Select object
        $approverSubSelect = $connection->select()
            ->from(
                ['ugp_a' => $this->getTable('user_groups_permission')],
                ['group_id']
            )
            ->columns([
                'approver_ids' => new \Zend_Db_Expr(
                    'GROUP_CONCAT(DISTINCT ce.entity_id ORDER BY ce.entity_id SEPARATOR ",")'
                )
            ])
            ->joinInner(
                ['ce' => $this->getTable('customer_entity')],
                new \Zend_Db_Expr('FIND_IN_SET(ce.entity_id, ugp_a.order_approval) > 0'),
                []
            )
            ->group('ugp_a.group_id');
            
        // Create subquery for users using Magento's Select object
        $userSubSelect = $connection->select()
            ->from(
                ['ugp_u' => $this->getTable('user_groups_permission')],
                ['group_id']
            )
            ->columns([
                'user_ids' => new \Zend_Db_Expr(
                    'GROUP_CONCAT(DISTINCT ugp_u.user_id ORDER BY ugp_u.user_id SEPARATOR ",")'
                )
            ])
            ->group('ugp_u.group_id');
            
        // Main select with optimized joins
        $this->getSelect()->reset(\Zend_Db_Select::COLUMNS)
            ->from(
                ['ug' => $this->getTable('user_groups')],
                ['id', 'group_name']
            )
            ->join(
                ['ugp' => $this->getTable('user_groups_permission')],
                'ug.id = ugp.group_id',
                []
            )
            ->join(
                ['companytable' => $this->getTable('company')],
                'ugp.company_id = companytable.entity_id',
                ['company_name']
            )
            // Use LEFT JOIN with subqueries instead of direct joins
            ->joinLeft(
                ['approvers' => $approverSubSelect],
                'ug.id = approvers.group_id',
                ['order_approvers_names' => 'approver_ids']
            )
            ->joinLeft(
                ['users' => $userSubSelect],
                'ug.id = users.group_id',
                ['user_names' => 'user_ids']
            )
            ->where('ug.group_type = ?', 'order_approval')
            ->group(['ug.id', 'ug.group_name', 'companytable.company_name']);
        
        return $this;
    }
}
