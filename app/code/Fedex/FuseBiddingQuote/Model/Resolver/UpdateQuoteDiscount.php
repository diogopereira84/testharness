<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\FuseBiddingQuote\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Fedex\UploadToQuote\Helper\GraphqlApiHelper;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\FuseBiddingQuote\Helper\fuseBidGraphqlHelper;
use Psr\Log\LoggerInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;

/**
 * UpdateQuoteDiscount class graphql API
 */
class UpdateQuoteDiscount implements ResolverInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMask $quoteIdMaskResource
     * @param GraphqlApiHelper $graphqlApiHelper
     * @param fuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param LoggerInterface $logger
     * @param FuseBidHelper $fuseBidHelper
     */
    public function __construct(
        private CartRepositoryInterface $quoteRepository,
        private QuoteIdMask $quoteIdMaskResource,
        private GraphqlApiHelper $graphqlApiHelper,
        protected fuseBidGraphqlHelper  $fuseBidGraphqlHelper,
        private LoggerInterface $logger,
        protected FuseBidHelper $fuseBidHelper
    ) {
    }

    /**
     * Resolve method for GraphQL.
     *
     * @param Field $field
     * @param mixed $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!$this->fuseBidGraphqlHelper->validateToggleConfig()) {
            throw new GraphQlInputException(__("Fuse Bidding toggle is not enabled"));
        };
        $this->fuseBidGraphqlHelper->validateCartUid($args);
        $quoteId = $this->getQuoteIdFromArgs($args);
        $discountIntent = $args['discountIntent'] ?? null;
        /** B-2388454 check start */
        $commentText = $args['comment'] ?? null;
        $this->handleComment($quoteId, $commentText);
        /** B-2388454 check End */
        $quote = $this->quoteRepository->get($quoteId);
        $this->saveDiscountIntent($quote, $discountIntent);
        $this->graphqlApiHelper->getRateResponse($quote, null, $discountIntent);

        return $this->processQuote($quote, $quoteId);
    }

    /**
     * Get the unmasked quote ID from the cart_uid argument.
     *
     * @param array $args
     * @return int
     */
    private function getQuoteIdFromArgs($args)
    {
        return $this->quoteIdMaskResource->getUnmaskedQuoteId($args['uid']);
    }

    /**
     * Process the negotiable quote.
     *
     * @param Obj $quote
     * @param int $quoteId
     * @return array|null
     * @throws GraphQlInputException
     */
    private function processQuote($quote, $quoteId)
    {
        try {

            return $this->prepareQuoteResponse($quote, $quoteId);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

     /**
      * Prepare the response for the negotiable quote.
      *
      * @param Obj $quote
      * @param int $quoteId
      * @return array
      */
    private function prepareQuoteResponse($quote, $quoteId)
    {
        $quoteInfo = $this->graphqlApiHelper->getQuoteInfo($quote);
        $rateResponse = $this->graphqlApiHelper->getRateResponse($quote);

        return [
            'quote_id' => $quoteInfo['quote_id'],
            'quote_status' => $quoteInfo['quote_status'],
            'hub_centre_id' => $quoteInfo['hub_centre_id'],
            'is_bid' => $quote->getIsBid(),
            'location_id' => $quoteInfo['location_id'],
            'quote_creation_date' => $quoteInfo['quote_creation_date'],
            'quote_updated_date' => $quoteInfo['quote_updated_date'],
            'quote_expiration_date' => $quoteInfo['quote_expiration_date'],
            'contact_info' => $this->graphqlApiHelper->getQuoteContactInfo($quote),
            'rateSummary' => $this->graphqlApiHelper->getRateSummaryData($rateResponse),
            'line_items' => $this->graphqlApiHelper->getQuoteLineItems($quote, $rateResponse),
            'fxo_print_account_number' => $this->graphqlApiHelper->getFxoAccountNumberOfQuote($quote),
            'activities' => $this->graphqlApiHelper->getQuoteNotes($quoteId),
            'nbc_required' => $quote->getNbcRequired(),
            'lte_identifier' => $quote->getLteIdentifier() ?? null
        ];
    }

    /**
     * Save the discountIntent
     *
     * @param obj $quote
     * @param mix $discountIntent
     * @return void
     */
    private function saveDiscountIntent($quote, $discountIntent)
    {
        $quoteItem = $quote->getAllVisibleItems();
        foreach ($quoteItem as $item) {
            $infoBuyRequest = $item->getOptionByCode('info_buyRequest');
            if ($infoBuyRequest && $infoBuyRequest->getValue()) {
                $decodedProductData = json_decode($infoBuyRequest->getValue(), true);
                $decodedProductData['discountIntent']= $discountIntent;
                $encodedProductData = json_encode($decodedProductData);
                $infoBuyRequest->setvalue($encodedProductData);
                $item->save();
            }
        }
    }

    /**
     * Handles comment validation and saving if the toggle is enabled.
     *
     * @param int $quoteId
     * @param string|null $commentText
     * @return void
     * @throws GraphQlInputException
     */
    private function handleComment(int $quoteId, ?string $commentText): void
    {
        if (!$commentText) {
            return;
        }

        if ($this->fuseBidHelper->isToggleTeamMemberInfoEnabled()) {
            $this->fuseBidGraphqlHelper->validateComment($commentText);
            $this->fuseBidGraphqlHelper->saveNegotiableQuoteComment($quoteId, $commentText);
        }
    }
}
