<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\UploadToQuote\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Psr\Log\LoggerInterface;

/**
 * Cronjob ExpireQuoteStatusChange
 *
 */
class ExpireQuoteStatusChange
{
    /**
     * @var EmailDataHelper
     */
    protected $data;

    /**
     * @var array $allowedStatuses
     */
    private $allowedStatuses = [
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER,
        NegotiableQuoteInterface::STATUS_PROCESSING_BY_ADMIN,
        NegotiableQuoteInterface::STATUS_CREATED,
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_CUSTOMER,
        NegotiableQuoteInterface::STATUS_SUBMITTED_BY_ADMIN,
    ];

    /**
     * Cronjob Constructor
     *
     * @param LoggerInterface $logger
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param TimezoneInterface $timezoneInterface
     * @param AdminConfigHelper $adminConfigHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected FilterBuilder $filterBuilder,
        protected TimezoneInterface $timezoneInterface,
        private AdminConfigHelper $adminConfigHelper
    )
    {
    }

    /**
     * Change Quote status
     *
     * @return void
     */
    public function execute()
    {
        $this->logger
        ->info(__METHOD__ . ':' . __LINE__ . ' ExpireQuoteStatusChange cron Executing..');
        $expiringDate = $this->timezoneInterface->date()->modify('+1 day')->format('Y-m-d');
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField('extension_attribute_negotiable_quote.expiration_period')
                    ->setConditionType('eq')
                    ->setValue($expiringDate)
                    ->create(),
            ]
        );
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField('extension_attribute_negotiable_quote.status')
                    ->setConditionType('in')
                    ->setValue($this->allowedStatuses)
                    ->create(),
            ]
        );
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField('extension_attribute_negotiable_quote.status_email_notification')
                    ->setConditionType('eq')
                    ->setValue(0)
                    ->create(),
            ]
        );
        
        $quoteIds = [];
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $quotelist = $this->negotiableQuoteRepository->getList($searchCriteria)->getItems();
        if (!empty($quotelist)) {
            foreach ($quotelist as $quoteId => $v) {
                if (!empty($quoteId)) {
                    // change quote status to processing_by_customer
                    $this->adminConfigHelper->updateQuoteStatusByKey(
                        $quoteId,
                        NegotiableQuoteInterface::STATUS_PROCESSING_BY_CUSTOMER
                    );
                   if($this->adminConfigHelper->quoteexpiryIssueFixToggle()){
                        // change quote status to expired
                        $this->adminConfigHelper->updateQuoteStatusByKey(
                            $quoteId,
                            NegotiableQuoteInterface::STATUS_EXPIRED
                        );
                    }
                    $quoteIds[] = $quoteId;
                }
                $this->logger
                ->info(__METHOD__ . ':' . __LINE__ . ' Quote Status Change Details for Date: ' . $expiringDate);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' status changed for QuoteIDs : '
                 . json_encode($quoteIds));
            }
        }
    }
}
