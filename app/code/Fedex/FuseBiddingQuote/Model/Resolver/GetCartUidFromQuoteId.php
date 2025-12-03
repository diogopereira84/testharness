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
use Magento\Framework\App\RequestInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;

/**
 * GetCartUidFromQuoteId class graphql API
 */
class GetCartUidFromQuoteId implements ResolverInterface
{
    private const CUSTOM_HEADER_NAME = 'X-Unique-Header-Fuse';

    /**
     * InitializeNegotiableQuote constructor.
     *
     * @param QuoteIdMask $quoteIdMaskResource
     * @param RequestInterface $request
     */
    public function __construct(
        private QuoteIdMask $quoteIdMaskResource,
        private RequestInterface $request
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
        $this->validateHeader();
        $this->validateArgs($args);

        return ['cart_uid' => $this->quoteIdMaskResource->getMaskedQuoteId($args['quote_id'])];
    }

    /**
     * Validate the custom header.
     *
     * @throws GraphQlInputException
     */
    private function validateHeader(): void
    {
        $headerValue = $this->request->getHeader(self::CUSTOM_HEADER_NAME);
        if ($headerValue !== 'xmen_fuse_bidding_quote') {
            throw new GraphQlInputException(__('Header %1 is missing or invalid.', self::CUSTOM_HEADER_NAME));
        }
    }

    /**
     * Validate the provided arguments.
     *
     * @param array|null $args
     * @throws GraphQlInputException
     */
    private function validateArgs($args): void
    {
        if (empty($args['quote_id'])) {
            throw new GraphQlInputException(__("quote_id value must be specified."));
        }
    }
}
