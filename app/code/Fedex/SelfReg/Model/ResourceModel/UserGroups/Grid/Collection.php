<?php
declare(strict_types=1);

namespace Fedex\SelfReg\Model\ResourceModel\UserGroups\Grid;

use Fedex\Login\Helper\Login;
use Fedex\SelfReg\Model\ResourceModel\UserGroups;
use Magento\Customer\Model\Session;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Expr;
use Magento\Framework\DB\Select;

class Collection extends SearchResult
{
    /**
     * Grid Collection class constructor
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param Session $customerSession
     * @param Login $loginHelper
     * @param string $mainTable
     * @param string $resourceModel
     *
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        protected Session $customerSession,
        protected Login $loginHelper,
        $mainTable = 'user_groups',
        $resourceModel = UserGroups::class
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );
    }

    /**
     * Get user groups for company
     *
     * @param int $companyId
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getUserGroups($companyId)
    {
        $this->getSelect()->reset()->from(
            ['ug' => $this->getTable('user_groups')],
            []
        )->join(
            ['permissions' => $this->getTable('user_groups_permission')],
            'ug.id = permissions.group_id',
            []
        )->where(
            'permissions.company_id = ?',
            $companyId
        )->columns([
            'group_name' => new Zend_Db_Expr("REGEXP_REPLACE(ug.group_name, '<.*>\\s*', '')"),
            'group_type' => new Zend_Db_Expr('ug.group_type'),
            'created_at' => new Zend_Db_Expr('ug.created_at'),
            'users' => new Zend_Db_Expr('COUNT(permissions.user_id)'),
            'id' => new Zend_Db_Expr("CONCAT('user_groups-', ug.id)"),
            'site_url' => new Zend_Db_Expr("
                CASE
                    WHEN ug.group_name REGEXP '^<[^>]+> '
                    THEN REGEXP_REPLACE(ug.group_name, '^(<[^>]+>).+$', '\\\\1')
                    ELSE NULL
                END
            "),
        ])->group(
            'ug.id'
        );

        return clone $this->getSelect();
    }

    /**
     * Get customer groups for company
     *
     * @param int $companyId
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getCustomerGroups($companyId)
    {
        $this->getSelect()->reset()->from(
            ['companytable' => $this->getTable('company')],
            []
        )->where(
            'companytable.entity_id = ?',
            $companyId
        )->join(
            ['pcg' => $this->getTable('parent_customer_group')],
            'companytable.customer_group_id = pcg.parent_group_id',
            []
        )->join(
            ['cg' => $this->getTable('customer_group')],
            'pcg.customer_group_id = cg.customer_group_id',
            []
        )->joinLeft(
            ['customertable' => $this->getTable('customer_entity')],
            'cg.customer_group_id = customertable.group_id',
            []
        )->columns([
            'group_name' => new Zend_Db_Expr("REGEXP_REPLACE(cg.customer_group_code, '<.*>\\s*', '')"),
            'group_type' => new Zend_Db_Expr("'Folder Permissions'"),
            'created_at' => new Zend_Db_Expr('pcg.created_at'),
            'users' => new Zend_Db_Expr('COUNT(customertable.group_id)'),
            'id' => new Zend_Db_Expr("CONCAT('customer_group-', cg.customer_group_id)"),
            'site_url' => new Zend_Db_Expr("
                CASE
                    WHEN cg.customer_group_code REGEXP '^<[^>]+> '
                    THEN REGEXP_REPLACE(cg.customer_group_code, '^(<[^>]+>).+$', '\\\\1')
                    ELSE NULL
                END
            "),
        ])->group(
            'cg.customer_group_id'
        );

        return clone $this->getSelect();
    }

    /**
     * Override _initSelect to include union query
     *
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        
        $customerId = $this->customerSession->getCustomer()->getId();

        if ($customerId) {
            $companyId = $this->loginHelper->getCompanyId($customerId);

            if ($companyId) {
                $manageUserGroupsUnionQuery = clone $this->getSelect()->reset()->union(
                    [
                        (string)$this->getCustomerGroups($companyId),
                        (string)$this->getUserGroups($companyId)
                    ],
                    Select::SQL_UNION_ALL
                );
                $manageUserGroupsUnionQuery->reset('from');

                $this->getSelect()->reset()->from(
                    ['main_table' => new Zend_Db_Expr('(' . (string)$manageUserGroupsUnionQuery . ')')]
                );
            }
        }
        return $this;
    }
}
