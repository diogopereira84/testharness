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
use Fedex\UploadToQuote\Helper\QuoteEmailHelper;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;

/**
 * SendQuoteEmail class graphql API
 */
class SendQuoteEmail implements ResolverInterface
{

    /**
     * SendQuoteEmail constructor.
     *
     * @param QuoteEmailHelper $quoteEmailHelper
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        protected QuoteEmailHelper $quoteEmailHelper,
        protected QuoteIdMask $quoteIdMaskResource
    )
    {
    }

    /**
     * Resolve method for retrieving cart UID from quote ID.
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
    ): ?array {
        
        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($args['uid']);
        $nbc = false;
        if ($args['template'] == 'NBC_SUPPORT') {
            $nbc = true;
        }

        $quoteData=[
            'quote_id' => $quoteId,
            'status' => 'submitted_by_admin',
            'is_bid' => 1,
            'nbc' => $nbc,
        ];

        try {
            $this->quoteEmailHelper->sendQuoteGenericEmail($quoteData);
        } catch (\Exception $e) {

            return [
                "status" => "failure",
                "failure_message" => $e->getMessage(),
            ];
        }
        
        return ["status" => "success"];
    }
}
