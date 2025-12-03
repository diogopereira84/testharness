<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\UploadToQuote\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Magento\NegotiableQuote\Model\NegotiableQuoteFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

class GetQuoteDetails implements ResolverInterface
{
    /** @var array $result */
    protected $result = [];
    public const ORDERED = 'ordered';


    /** @var $rateResponse  */
     protected $rateResponse;

    /**
     * @param QuoteIdMask $quoteIdMaskResource
     * @param CartRepositoryInterface $quoteRepository
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param FuseBidViewModel $fuseBidViewModel
     * @param NegotiableQuoteFactory $negotiableQuoteFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     */
    public function __construct(
        protected QuoteIdMask $quoteIdMaskResource,
        protected CartRepositoryInterface $quoteRepository,
        private GraphqlApiHelper $graphqlApiHelper,
        protected FuseBidViewModel $fuseBidViewModel,
        protected NegotiableQuoteFactory $negotiableQuoteFactory,
        protected LoggerHelper $loggerHelper,
        protected NewRelicHeaders $newRelicHeaders
    )
    {
    }

    /**
     * Resolve function
     *
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|\Magento\Framework\GraphQl\Query\Resolver\Value|mixed|string
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['uid'])) {
            throw new GraphQlInputException(__("uid value must be specified."));
        }
        $mutationName = $field->getName() ?? '';
        $headerArray = $this->newRelicHeaders->getHeadersForMutation($mutationName);
        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            $unifiedQuoteId = $args['uid'];
            $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($unifiedQuoteId);
            $logData['quoteId'] = $quoteId;
            $logData['query'] = $info->fieldName;
            $logData['variables'] = json_encode($args);
            $this->graphqlApiHelper->addLogsForGraphqlApi($logData);
            $quote = $this->quoteRepository->get($quoteId);
            $notes = $this->graphqlApiHelper->getQuoteNotes($quoteId);
            $quoteInfo = $this->graphqlApiHelper->getQuoteInfo($quote);
            $result['quote_id'] = $quoteInfo['quote_id'];
            $result['quote_status'] = $quoteInfo['quote_status'];
            $result['hub_centre_id'] = $quoteInfo['hub_centre_id'];
            $result['location_id'] = $quoteInfo['location_id'];
            $result['quote_creation_date'] = $quoteInfo['quote_creation_date'];
            $result['quote_updated_date'] = $quoteInfo['quote_updated_date'];
            $result['quote_submitted_date'] = $quoteInfo['quote_submitted_date'];
            $result['quote_expiration_date'] = $quoteInfo['quote_expiration_date'];
            $result['contact_info'] =  $this->graphqlApiHelper->getQuoteContactInfo($quote);
            $negotiableQuoteData = $this->negotiableQuoteFactory->create()->load($quoteId);

            if($negotiableQuoteData->getStatus() == self::ORDERED) {
                $orderIncrementId = $quote->getReservedOrderId();
                $result['rateSummary'] = $this->graphqlApiHelper->getRateSummaryDataForApprovedQuote($orderIncrementId);
                $result['line_items'] = $this->graphqlApiHelper->getQuoteLineItemsForApprovedQuote($orderIncrementId);
            } else {
                $this->rateResponse = $this->graphqlApiHelper->getRateResponse($quote);
                $result['rateSummary'] = $this->graphqlApiHelper->getRateSummaryData($this->rateResponse);
                $result['line_items'] = $this->graphqlApiHelper->getQuoteLineItems($quote, $this->rateResponse);
            }

            $result['fxo_print_account_number'] =  $this->graphqlApiHelper->getFxoAccountNumberOfQuote($quote);
            $customerId = $quote->getCustomerId();
            if ($customerId) {
                $result['company'] = $this->graphqlApiHelper->getQuoteCompanyName($quote);
            }
            $result['activities'] = $notes;
            if ($this->fuseBidViewModel->isFuseBidToggleEnabled()) {
                $result['is_bid'] = $quote->getIsBid();
                $result['nbc_required'] = $quote->getNbcRequired();
            }
            $result['coupon_code'] = $quote->getCouponCode();
            $result['lte_identifier'] = $quote->getLteIdentifier() ?? null;
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);

        } catch (\Exception $exception) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . $exception->getMessage(), $headerArray);
            throw new GraphQlInputException(__($exception->getMessage()));
        }

        return $result;
    }
}
