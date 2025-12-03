<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\UploadToQuote\Model\Resolver;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Fedex\UploadToQuote\Api\NegotiableQuoteIntegrationInterface;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\Framework\Api\FilterBuilder;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;

class GetAllQuotes implements ResolverInterface
{
    /** @var $statusEnumValues  */
    private $statusEnumValues = ['CREATED', 'EXPIRED', 'SENT', 'CANCELED', 'REQUESTED',
        'ORDERED', 'CHANGE_REQUESTED', 'CLOSED', 'NBC_PRICED', 'NBC_SUPPORT'];

    /** @var $statusEnumValues  */
    private $statusMapping = [
        'CREATED' => NegotiableQuoteInterface::STATUS_CREATED, /*quote created */
        'EXPIRED' => NegotiableQuoteInterface::STATUS_EXPIRED, /*quote expired */
        'SENT' => NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
        /*reviewd by team member and sent back to customer */
        'CHANGE_REQUESTED' => NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
        /*change requested by customer for a reviewed quote */
        'CANCELED' => NegotiableQuoteInterface::STATUS_DECLINED, /*declined by customer */
        'REQUESTED' => NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
        /* submitted by customer for team members review */
        'ORDERED' => NegotiableQuoteInterface::STATUS_ORDERED, /*approved and ordered by customer */
        'CLOSED' => NegotiableQuoteInterface::STATUS_CLOSED, /* cancelled by admin */
        'NBC_SUPPORT' => AdminConfigHelper::NBC_SUPPORT ,
        'NBC_PRICED' => AdminConfigHelper::NBC_PRICED
    ];

    /**
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param QuoteIdMask $quoteIdMaskResource
     * @param TimezoneInterface $timezoneInterface
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param SortOrderBuilder $sortOrderBuilder
     * @param FuseBidViewModel $fuseBidViewModel
     * @param FilterBuilder $filterBuilder
     * @param ConfigInterface $instoreConfig
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param NegotiableQuoteIntegrationInterface $negotiableQuoteIntegrationRepository
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        private NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private QuoteIdMask $quoteIdMaskResource,
        private TimezoneInterface $timezoneInterface,
        private GraphqlApiHelper $graphqlApiHelper,
        private SortOrderBuilder $sortOrderBuilder,
        protected FuseBidViewModel $fuseBidViewModel,
        protected FilterBuilder $filterBuilder,
        protected readonly ConfigInterface $instoreConfig,
        protected readonly LoggerHelper $loggerHelper,
        protected readonly NewRelicHeaders $newRelicHeaders,
        private readonly NegotiableQuoteIntegrationInterface $negotiableQuoteIntegrationRepository,
        protected AdminConfigHelper $adminConfigHelper
    ) {
    }

    /**
     * Resolve function
     *
     * @throws GraphQlInputException
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $headerId = '';
        if ($this->instoreConfig->isSpanIdLoggedForGetAllQuotes()) {
            $headerId = $this->newRelicHeaders->addSpanIdToNewrelicLogsForGetAllQuotes();
            if ($headerId) {
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerId);
            }
        }
        $filterArgs = $args['filter'];
        $logData['query'] = $info->fieldName;
        $logData['variables'] = json_encode($args);
        $this->graphqlApiHelper->addLogsForGraphqlApi($logData);
        if (isset($filterArgs['quote_id'])) {
            return $this->fetchQuoteById($filterArgs['quote_id']);
        }
        $this->validateDateFilter($filterArgs);
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && isset($filterArgs['price_filter'])) {
            $this->validatePriceFilter($filterArgs);
        }
        $this->applyFilters($filterArgs);
        $this->handleSortOrder($filterArgs);
        $this->searchCriteriaBuilder
            ->setPageSize(100)
            ->setCurrentPage(1);
        if ($this->instoreConfig->isFilterForGetAllQuotesEnabled()) {
            $negotiableQuotesObj = $this->negotiableQuoteIntegrationRepository->getList($this->searchCriteriaBuilder->create());
        } else {
            $negotiableQuotesObj = $this->negotiableQuoteRepository->getList($this->searchCriteriaBuilder->create());
        }
        $totalCount = $negotiableQuotesObj->getTotalCount();
        $negotiableQuotes = $negotiableQuotesObj->getItems();
        $quotesResult = $this->createQuotesResult($negotiableQuotes, $filterArgs['contact_info'] ?? []);
        if (isset($filterArgs['order_by']) && $filterArgs['order_by'] == 'quote_status') {
            $quotesResult = $this->handleSortingForQuoteStatus($quotesResult, $filterArgs['order']);
        }
        if ($this->instoreConfig->isSpanIdLoggedForGetAllQuotes()) {
            if ($headerId) {
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerId);
            }
        }
        return [
            'quotes' => $quotesResult,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Fetch negotiable quotes by quote ID.
     *
     * @param int $quoteId
     * @return array
     */
    private function fetchQuoteById($quoteId)
    {
        $this->searchCriteriaBuilder->addFilter('quote_id', $quoteId);
        $quotesList = $this->negotiableQuoteRepository->getList($this->searchCriteriaBuilder->create());
        $quotesResult = $this->getFilteredQuotesData($quotesList);

        return [
            'quotes' => $quotesResult,
            'total_count' => count($quotesResult),
        ];
    }

    /**
     * Validate date Filter
     *
     * @param array $filterArgs
     * @return void
     */
    private function validateDateFilter(array $filterArgs)
    {
        if (!isset($filterArgs['date_filter']) ||
            empty($filterArgs['date_filter']['type']) ||
            empty($filterArgs['date_filter']['start_date']) ||
            empty($filterArgs['date_filter']['end_date'])
        ) {
            throw new GraphQlInputException(
                !isset($filterArgs['date_filter'])
                ? __('date_filter is mandatory')
                : __('date_filter type, start_date, and end_date are required')
            );
        }
        if (!in_array($filterArgs['date_filter']['type'], ['CREATED', 'EXPIRED'])) {
            throw new GraphQlInputException(__('Invalid type. Allowed values are: CREATED, EXPIRED.'));
        }
    }

    /**
     * Validate price Filter
     *
     * @param array $filterArgs
     * @return void
     */
    private function validatePriceFilter(array $filterArgs)
    {
        if (strlen($filterArgs['price_filter']['min_price']) == 0) {
            throw new GraphQlInputException(__('price_filter min_price is required'));
        } else {
            $minInput = $filterArgs['price_filter']['min_price'];
            $minAllowed = 0;
            $maxAllowed = 999999;
            if (!is_numeric($minInput)) {
                throw new GraphQlInputException(__('Please enter a number in min_price'));
            }
            if (!($minInput >= $minAllowed && $minInput <= $maxAllowed)) {
                throw new GraphQlInputException(__('Allowed price filter min_price values are: 0 - 999999'));
            }
        }
    }

    /**
     * Apply Filter
     *
     * @param array $filterArgs
     * @return void
     */
    private function applyFilters(array $filterArgs)
    {
        $quoteNames = ['Upload To Quote Creation'];
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $quoteNames[]= 'FUSE bidding Quote Creation';
        }
        $quoteNameFilter = $this->filterBuilder
            ->setField('quote_name')
            ->setConditionType('in')
            ->setValue($quoteNames)
            ->create();

        $this->searchCriteriaBuilder->addFilters([$quoteNameFilter]);
        if (isset($filterArgs['quote_status'])) {
            $this->handleQuoteStatusFilter($filterArgs['quote_status']);
        }
        if (isset($filterArgs['hub_centre_id'])) {
            $this->searchCriteriaBuilder->addFilter(
                'extension_attribute_negotiable_quote.quote_mgnt_location_code',
                $filterArgs['hub_centre_id']
            );
        }

        if (isset($filterArgs['date_filter'])) {
            $this->handleQuoteDateFilter($filterArgs['date_filter']);
        }
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && isset($filterArgs['price_filter'])) {
            $this->handleQuotePriceFilter($filterArgs['price_filter']);
        }
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled() && isset($filterArgs['nbc_required'])) {
            $this->searchCriteriaBuilder
            ->addFilter('nbc_required', $filterArgs['nbc_required']);
        }

        $minLen = 2;
        if ((!empty($filterArgs['contact_info']['first_name']) &&
            strlen($filterArgs['contact_info']['first_name']) < $minLen) ||
            (!empty($filterArgs['contact_info']['last_name']) &&
                strlen($filterArgs['contact_info']['last_name']) < $minLen) ||
            (!empty($filterArgs['contact_info']['email']) &&
                strlen($filterArgs['contact_info']['email']) < $minLen) ||
            (!empty($filterArgs['contact_info']['phone_number']) &&
                strlen($filterArgs['contact_info']['phone_number']) < $minLen)
        ) {
            throw new GraphQlInputException(
                __('Minimun 2 characters needed to serach for First Name, Last Name, Email & Phone.')
            );
        }

        if (isset($filterArgs['contact_info']['first_name']) &&
            !empty($filterArgs['contact_info']['first_name'])
        ) {
            $this->searchCriteriaBuilder
            ->addFilter('customer_firstname', $filterArgs['contact_info']['first_name'] . '%', 'like');
        }
        if (isset($filterArgs['contact_info']['last_name']) &&
            !empty($filterArgs['contact_info']['last_name'])
        ) {
            $this->searchCriteriaBuilder
            ->addFilter('customer_lastname', $filterArgs['contact_info']['last_name'] . '%', 'like');
        }
        if (isset($filterArgs['contact_info']['email']) &&
            !empty($filterArgs['contact_info']['email'])
        ) {
            $this->searchCriteriaBuilder
            ->addFilter('customer_email', $filterArgs['contact_info']['email'] . '%', 'like');
        }
        if (isset($filterArgs['contact_info']['phone_number']) &&
            !empty($filterArgs['contact_info']['phone_number'])
        ) {
            $this->searchCriteriaBuilder
            ->addFilter('customer_telephone', $filterArgs['contact_info']['phone_number'] . '%', 'like');
        }
    }

    /**
     * Handle Quote Status Filter
     *
     * @param string $quoteStatus
     * @return void
     */
    private function handleQuoteStatusFilter($quoteStatus)
    {
        if (!in_array($quoteStatus, $this->statusEnumValues)) {
            throw new GraphQlInputException(__('Invalid status. Allowed values are: ' . implode(
                ', ',
                $this->statusEnumValues
            )));
        }
        $status = $this->statusMapping[$quoteStatus];
        $this->searchCriteriaBuilder->addFilter('status', $status);
    }

    /**
     * Handle Quote Date Filter
     *
     * @param string $dateFilter
     * @return void
     */
    private function handleQuoteDateFilter($dateFilter)
    {
        $startDate = $dateFilter['start_date'] . ' 00:00:00';
        $endDate = $dateFilter['end_date'] . ' 23:59:59';
        $dateDiff = strtotime($endDate) - strtotime($startDate);
        $oneMonthInSeconds = 30 * 24 * 60 * 60;
        if ($dateDiff > $oneMonthInSeconds) {
            $endDate = $this->timezoneInterface->date(strtotime('+1 month', strtotime($startDate)))
                ->format("Y-m-d H:i:s");
        }
        if ($dateFilter['type'] == 'CREATED') {
            $this->searchCriteriaBuilder->addFilter('converted_at', $startDate, 'gteq');
            $this->searchCriteriaBuilder->addFilter('converted_at', $endDate, 'lteq');
        }
        if ($dateFilter['type'] == 'EXPIRED') {
            $this->searchCriteriaBuilder->addFilter('expiration_period', $startDate, 'gteq');
            $this->searchCriteriaBuilder->addFilter('expiration_period', $endDate, 'lteq');
        }
    }

    /**
     * Handle Quote Price Filter
     *
     * @param string $priceFilter
     * @return void
     */
    private function handleQuotePriceFilter($priceFilter)
    {
        $minPrice = (float)$priceFilter['min_price'];
        if ($this->adminConfigHelper->isToggleD233151Enabled()){
            $this->searchCriteriaBuilder->addFilter('subtotal', $minPrice, 'gteq');
        } else {
            $this->searchCriteriaBuilder->addFilter('grand_total', $minPrice, 'gteq');
        }
        
    }

    /**
     * Process quote data and create result
     *
     * @param object $quote
     * @return array|null
     */
    private function processQuote($quote)
    {
        $quoteId = (int) $quote->getId();
        $notes = $this->graphqlApiHelper->getQuoteNotes($quoteId);
        $quoteInfo = $this->graphqlApiHelper->getQuoteInfo($quote);
        $result = [
        'quote_id' => $quoteInfo['quote_id'],
        'uid' => $this->quoteIdMaskResource->getMaskedQuoteId($quoteId),
        'quote_status' => $quoteInfo['quote_status'],
        'quote_creation_date' => $quoteInfo['quote_creation_date'],
        'quote_updated_date' => $quoteInfo['quote_updated_date'],
        'quote_submitted_date' => $quoteInfo['quote_submitted_date'],
        'quote_expiration_date' => $quoteInfo['quote_expiration_date'],
        'gross_amount' => $quoteInfo['gross_amount'],
        'discount_amount' => $quoteInfo['discount_amount'],
        'quote_total' =>  $quoteInfo['quote_total'],
        'hub_centre_id' => $quoteInfo['hub_centre_id'],
        'location_id' => $quoteInfo['location_id'],
        'contact_info' => $this->graphqlApiHelper->getQuoteContactInfo($quote),
        'activities' => $notes
        ];
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
            $result['is_bid'] = $quote->getIsBid();
            $result['nbc_required'] = $quote->getNbcRequired();
        }

        return $result;
    }

    /**
     * Create Result for negotiable quotes
     *
     * @param object $negotiableQuotes
     * @param array $contactInfoRequest
     * @return array
     */
    private function createQuotesResult($negotiableQuotes, $contactInfoRequest)
    {
        $quotesResult = [];
        if (empty($negotiableQuotes)) {
            return $quotesResult;
        }
        foreach ($negotiableQuotes as $quote) {
            $result = $this->processQuote($quote, $contactInfoRequest);
            if ($result !== null) {
                $quotesResult[] = $result;
            }
        }

        return $quotesResult;
    }

    /**
     * Get filtered Quote Data
     *
     * @param object $quotelist
     * @return array
     */
    public function getFilteredQuotesData($quotelist)
    {
        $quotesResult = [];
        foreach ($quotelist->getItems() as $quote) {
            $result = $this->processQuote($quote, []);
            if ($result !== null) {
                $quotesResult[] = $result;
            }
        }

        return $quotesResult;
    }

    /**
     * Handle Sort Order
     *
     * @param array $filterArgs
     * @return void
     */
    private function handleSortOrder($filterArgs)
    {
        $allowedOrderByValues = ['quote_id', 'hub_centre_id', 'quote_status',
        'email', 'first_name', 'last_name', 'phone_number', 'quote_creation_date','quote_updated_date',
         'quote_expiration_date'];
        if (!isset($filterArgs['order_by'])) {
            return;
        }
        $orderByMapping = [
            "quote_id" => "quote_id",
            "hub_centre_id" => "quote_mgnt_location_code",
            "quote_status" => "extension_attribute_negotiable_quote_status",
            "email" => "customer_email",
            "first_name" => "customer_firstname",
            "last_name" => "customer_lastname",
            "phone_number" => "customer_telephone",
            "quote_creation_date" => "created_at",
            "quote_updated_date" => "updated_at",
            "quote_expiration_date" => "created_at",
        ];
        $orderBy = $orderByMapping[strtolower($filterArgs['order_by'] ?? '')] ?? '';
        if ($orderBy === '') {
            throw new GraphQlInputException(
                __("Invalid order_by value. Allowed values are: %1", implode(', ', $allowedOrderByValues))
            );
        }
        $order = $filterArgs['order'] ?? "ASC";
        $sortOrder = $this->sortOrderBuilder
            ->setField($orderBy)
            ->setDirection($order)
            ->create();
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);
    }

    /**
     * Handle Sort Order for quote_status
     *
     * @param array $quotes
     * @param string $order
     * @return array
     */
    public function handleSortingForQuoteStatus($quotes, $order)
    {
        usort($quotes, function ($a, $b) use ($order) {
            if ($order === "ASC") {
                return strcmp($a['quote_status'], $b['quote_status']);
            } elseif ($order === "DESC") {
                return strcmp($b['quote_status'], $a['quote_status']);
            }
        });

        return $quotes;
    }
}
