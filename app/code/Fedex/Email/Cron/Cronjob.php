<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Email\Cron;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteInterface;
use Magento\NegotiableQuote\Api\NegotiableQuoteRepositoryInterface;
use Magento\NegotiableQuote\Model\NegotiableQuoteRepository;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\Email\Helper\Data as EmailDataHelper;
use Fedex\Punchout\Helper\Data as PunchoutDataHelper;
use Psr\Log\LoggerInterface;
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Fedex\UploadToQuote\Helper\AdminConfigHelper;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;

/**
 * Cronjob Cronjob
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class Cronjob
{
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
     * Fuse constant for expired email
     */
    public const FUSE_BIDDING_QUOTE_CREATION = 'FUSE bidding Quote Creation';

    /**
     * Punchout Quote Creation
     */
    public const PUNCHOUT_QUOTE_CREATION = 'Punchout Quote Creation';

    /**
     * Upload To Quote Creation
     */
    public const UPLOAD_TO_QUOTE_CREATION = 'Upload To Quote Creation';

    /**
     * Cronjob Constructor
     *
     * @param LoggerInterface $logger
     * @param PunchoutDataHelper $helper
     * @param EmailDataHelper $data
     * @param NegotiableQuoteRepositoryInterface $negotiableQuoteRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param TimezoneInterface $localeDate
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param AdminConfigHelper $adminConfigHelper
     * @param GraphqlApiHelper $graphqlApiHelper
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected PunchoutDataHelper $helper,
        protected EmailDataHelper $data,
        protected NegotiableQuoteRepositoryInterface $negotiableQuoteRepository,
        protected SearchCriteriaBuilder $searchCriteriaBuilder,
        protected FilterBuilder $filterBuilder,
        protected TimezoneInterface $localeDate,
        protected CartRepositoryInterface $quoteRepository,
        protected QuoteEmailHelper $quoteEmailHelper,
        protected AdminConfigHelper $adminConfigHelper,
        protected GraphqlApiHelper $graphqlApiHelper
    )
    {
    }

    /**
     * Quote expired notification
     *
     * @return void
     */
    public function expiredQuoteNotification()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Expired Quote Notification Cron Executing..');
        $currentDate = date('Y-m-d');
        if ($this->adminConfigHelper->isUploadToQuoteToggle()) {
            $this->allowedStatuses[] = 'expired';
        }
        $this->searchCriteriaBuilder->addFilters(
            [
                $this->filterBuilder->setField('extension_attribute_negotiable_quote.expiration_period')
                    ->setConditionType('eq')
                    ->setValue($currentDate)
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
                    $qdetails = $this->quoteRepository->get($quoteId);
                    $status = 0;
                    $negotiableQuote = $qdetails->getExtensionAttributes()->getNegotiableQuote();
                    if ($negotiableQuote->getQuoteName()==self::PUNCHOUT_QUOTE_CREATION) {
                        $status = $this->data->OrderExpiredEmail($quoteId);
                    }
                    if ($this->adminConfigHelper->isUploadToQuoteToggle() &&
                    $negotiableQuote->getQuoteName()==self::UPLOAD_TO_QUOTE_CREATION) {
                        $quoteData=[
                            'quote_id' => $quoteId,
                            'status' => NegotiableQuoteInterface::STATUS_EXPIRED,
                        ];
                        $status = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                        $this->graphqlApiHelper->setQuoteNotes("Quote Expired", $quoteId, "quote_expired");

                    }
                    if ($negotiableQuote->getQuoteName()==self::FUSE_BIDDING_QUOTE_CREATION) {
                        $quoteData=[
                            'quote_id' => $quoteId,
                            'status' => NegotiableQuoteInterface::STATUS_EXPIRED,
                        ];
                        $status = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                        $this->graphqlApiHelper->setQuoteNotes("Quote Expired", $quoteId, "quote_expired");
                    }
                    // update email status flag to 1
                    $emailFlag = 1;
                    // @codeCoverageIgnoreStart
                    if ($status) {
                        $negotiableQuote->setEmailNotificationStatus($emailFlag)->save();
                    }
                    // @codeCoverageIgnoreEnd
                    $quoteIds[] = $quoteId;
                }
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Quote Expired Details for Date: ' . $currentDate);
                $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Email Notification Sent for QuoteIDs : '
                 . json_encode($quoteIds));
            }
        }
    }

    /**
     * Quote expiring notification
     *
     * @return void
     */
    public function quoteExpiringNotification()
    {
        $this->logger->info(__METHOD__ . ':' . __LINE__ . ' QuoteExpiringNotification Cron Works');
        $expiringDate = date('Y-m-d', strtotime("+5 days"));
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
                $this->filterBuilder->setField('extension_attribute_negotiable_quote.expiration_period')
                    ->setConditionType('neq')
                    ->setValue("0000-00-00")
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
                    $qdetails = $this->quoteRepository->get($quoteId);
                    $negotiableQuote = $qdetails->getExtensionAttributes()->getNegotiableQuote();
                    if ($negotiableQuote->getQuoteName()==self::PUNCHOUT_QUOTE_CREATION) {
                        $status = $this->data->OrderExpiringEmail($quoteId, $expiringDate);
                    }
                    if ($this->adminConfigHelper->isUploadToQuoteToggle() &&
                     $negotiableQuote->getQuoteName()==self::UPLOAD_TO_QUOTE_CREATION) {
                        $quoteData=[
                            'quote_id' => $quoteId,
                            'status' => 'expiration',
                        ];
                        $status = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                    }
                    if ($negotiableQuote->getQuoteName()==self::FUSE_BIDDING_QUOTE_CREATION) {
                        $quoteData=[
                            'quote_id' => $quoteId,
                            'status' => 'expiration',
                        ];
                        $status = $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
                    }
                    // @codeCoverageIgnoreStart
                    if ($status) {
                        $quoteIds[] = $quoteId;
                    }
                    // @codeCoverageIgnoreEnd
                }
            }
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Quote Expiring Details for Date: ' . $expiringDate);
            $this->logger->info(__METHOD__ . ':' . __LINE__ . ' Quote Expiring Notification sent for QuoteIds : '
             . json_encode($quoteIds));
        }
    }
}
