<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Company\Model\ResourceModel\Customer;

use Magento\Backend\Model\Session;
use Magento\Company\Api\Data\CompanyCustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

/**
 * Collection class for change admin grid
 *
 * @codeCoverageIgnore
 */
class Collection extends SearchResult
{
    /**
     * @var string
     */
    private $customerTypeExpressionPattern = '(IF(company_customer.company_id > 0, '
        . 'IF(company_customer.customer_id = company.super_user_id, "%d", "%d"), "%d"))';

    /**
     * @var string
     */
    private $customer_grid = 'customer_grid_flat';

    /**
     * Constuctor for collection class
     *
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param RequestInterface $request
     * @param Session $adminSession
     * @throws LocalizedException
     */
    public function __construct(
        protected EntityFactoryInterface $entityFactory,
        protected LoggerInterface $logger,
        protected FetchStrategyInterface $fetchStrategy,
        protected ManagerInterface $eventManager,
        protected RequestInterface $request,
        private Session $adminSession
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $this->customer_grid
        );
    }

    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->joinCompanyAdvancedCustomerEntityTable();
        $this->joinCompanyTable();
        $this->filterByCurrentCompany();
        $this->setSortOrder();
    }

    /**
     * Join company advanced customer entity table to main table
     *
     * @return void
     */
    private function joinCompanyAdvancedCustomerEntityTable()
    {
        $this->getSelect()->joinLeft(
            ['company_customer' => $this->getTable('company_advanced_customer_entity')],
            'company_customer.customer_id = main_table.entity_id',
            ['company_id', 'status']
        );
    }

    /**
     * Join company table to company advanced customer entity table
     *
     * @return void
     */
    private function joinCompanyTable()
    {
        $this->getSelect()->joinLeft(
            ['company' => $this->getTable('company')],
            'company.entity_id = company_customer.company_id',
            ['customer_role' => new \Zend_Db_Expr($this->prepareCustomerTypeColumnExpression())]
        );
    }

    /**
     * Filter data for current company
     *
     * @return void
     */
    private function filterByCurrentCompany()
    {
        $companyId = $this->adminSession->getCompanyAdminId();

        $this->getSelect()->where('company_id = ' . $companyId);
    }

    /**
     * Prepares value for customer type column
     *
     * @return void
     */
    private function prepareCustomerTypeColumnExpression()
    {
        return sprintf(
            $this->customerTypeExpressionPattern,
            CompanyCustomerInterface::TYPE_COMPANY_ADMIN,
            CompanyCustomerInterface::TYPE_COMPANY_USER,
            CompanyCustomerInterface::TYPE_INDIVIDUAL_USER
        );
    }

    /**
     * Set sort order
     *
     * @return void
     */
    private function setSortOrder()
    {
        $postData = $this->request->getParams();
        if (isset($postData['sorting'])) {
            $field = $postData['sorting']['field'];
            $direction = strtoupper($postData['sorting']['direction']);
            $this->getSelect()->order(new \Zend_Db_Expr("$field $direction"));
        } else {
            $adminSortOrder = isset($postData['savedAdminId']) ?
                'customer_id=' . $postData['savedAdminId'] : 'customer_role=0';
            $this->getSelect()->order(new \Zend_Db_Expr($adminSortOrder . ' DESC, name ASC'));
        }

    }
}
