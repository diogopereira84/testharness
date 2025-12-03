<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\UploadToQuote\Model\QuoteHistory;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Api\SortOrder;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Fedex\UploadToQuote\Model\ResourceModel\QuoteGrid\CollectionFactory as NegQuoteCollectionFactory;

/**
 * Class to provide Negotiable quote list
 */
class GetAllQuotes
{
    /**
     * @param UserContextInterface $userContext
     * @param RequestInterface $request
     * @param NegQuoteCollectionFactory $negQuoteCollectionFactory
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private   UserContextInterface $userContext,
        protected RequestInterface $request,
        protected NegQuoteCollectionFactory $negQuoteCollectionFactory,
        protected readonly ToggleConfig $toggleConfig
    )
    {
    }

    /**
     * Retrieve customer id
     *
     * @return int|null
     */
    protected function getCustomerId()
    {
        return $this->userContext->getUserId() ? : null;
    }

    /**
     * Get all negotiable quote
     *
     * @return object
     */
    public function getAllNegotiableQuote($count = false)
    {
        $statusArr = [
            NegotiableQuoteInterface::STATUS_CREATED, /*quote created and submitted by customer */
            NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
            /*submitted by customer for seller team members review*/
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
            /*reviewd by seller team member and sent back to customer*/
            NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
            /*change requested by customer for a reviewed quote */
            AdminConfigHelper::NBC_SUPPORT,
            AdminConfigHelper::NBC_PRICED,
        ];
        $quoteHistoryStatusArr = [
            NegotiableQuoteInterface::STATUS_ORDERED, //approved and ordered by customer
            NegotiableQuoteInterface::STATUS_DECLINED, //declined by customer
            NegotiableQuoteInterface::STATUS_CLOSED, //cancelled by seller team member
            NegotiableQuoteInterface::STATUS_EXPIRED //quote expired 30 days time period
        ];
        $dataSet = $this->request->getParam('dataset') ? $this->request->getParam('dataset') : '';
        $searchValue = $this->request->getParam('search') ? $this->request->getParam('search') : '';
        $sortValue = $this->request->getParam('sortby') ? $this->request->getParam('sortby') : 'created_at';
        $orderValue = $this->request->getParam('orderby') ? $this->request->getParam('orderby') : 'DESC';
        $sortDir = SortOrder::SORT_DESC;
        $sortByValue = 'created_at';
        if ($orderValue && $orderValue != 'DESC') {
            $sortDir = SortOrder::SORT_ASC;
        }
        if ($sortValue && $sortValue != 'created_at') {
            $sortByValue = $sortValue;
        }
        if ($sortValue && $sortValue == 'expiration') {
            $sortByValue = 'created_at';
        }
        if ($sortValue && $sortValue == 'status') {
            $sortByValue = 'status_label';
        }
        if ($dataSet && $dataSet == 2) {
            $statusArr = $quoteHistoryStatusArr;
        }
        if ($dataSet && $dataSet == 2) {
            $statusArr = $quoteHistoryStatusArr;
        }

        //get values of current page
        $page = ($this->request->getParam('p'))? $this->request->getParam('p') : 1;
        //get values of current limit
        $pageSize = ($this->request->getParam('limit'))? $this->request->getParam('limit') : 10;

        $customerId = $this->getCustomerId();
        $negQuoteCollection = $this->negQuoteCollectionFactory->create();
        $quoteTable = $negQuoteCollection->getTable('quote');
        $negQuoteGridTable = $negQuoteCollection->getTable('negotiable_quote_grid');
        $negQuoteCollection->addFieldToSelect(['entity_id', 'created_at', 'status','negotiated_grand_total']);
        if ($sortValue && $sortValue == 'status') {
            $negQuoteCollection->getSelect()
            ->columns([
                'status_label' => new \Zend_Db_Expr(
                    '(SELECT
                    (
                        CASE
                            WHEN status = "processing_by_admin" then "Store Review"
                            WHEN status = "submitted_by_admin" then "Ready for Review"
                            WHEN status = "submitted_by_customer" then "Change Requested"
                            WHEN status = "processing_by_customer" then "Set To Expire"
                            WHEN status = "ordered" then "Approved"
                            WHEN status = "nbc_support" then "NBC Support"
                            WHEN status = "nbc_priced" then "NBC Priced"
                            ELSE "Submitted"
                        END
                    ) FROM '.$negQuoteGridTable.' WHERE entity_id = main_table.entity_id)'
                )
            ]);
        }
        if ($this->isToggleD206707Enabled()) {
            $quoteCols = [
                'tax' => 'custom_tax_amount',
                'grand_total' => 'grand_total',
                'quote.quote_mgnt_location_code',
                'quote.is_epro_quote',
                'quote.sent_to_erp'
            ];
        } else {
            $quoteCols = ['tax' => 'custom_tax_amount', 'grand_total' => 'grand_total'];
        }
        $negQuoteCollection->join(
            $quoteTable,
            'main_table.entity_id='.$quoteTable.'.entity_id',
            $quoteCols
        );
        $negQuoteCollection->addFieldToFilter('main_table.customer_id', $customerId);
        if (!$count) {
            $negQuoteCollection->addFieldToFilter('main_table.status', ['in' => $statusArr]);
            if ($searchValue) {
                $negQuoteCollection->addFieldToFilter('main_table.entity_id', $searchValue);
            }
            $negQuoteCollection->setOrder($sortByValue, $sortDir);
            $negQuoteCollection->setCurPage($page);
        }
        $negQuoteCollection->setPageSize($pageSize);

        return $negQuoteCollection;
    }

    /**
     * Check if D-206707 toggle is enabled
     * @return bool
     */
    public function isToggleD206707Enabled()
    {
        return (bool)$this->toggleConfig->getToggleConfigValue(AdminConfigHelper::XML_PATH_TOGGLE_D206707);
    }
}
