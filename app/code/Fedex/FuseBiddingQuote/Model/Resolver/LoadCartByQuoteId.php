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
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutputFactory;
use Magento\Quote\Model\Cart\Data\Error as CartError;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;

/**
 * LoadCartByQuoteId class graphql API
 */
class LoadCartByQuoteId implements ResolverInterface
{
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    private const ERROR_NOT_SALABLE = 'NOT_SALABLE';
    private const ERROR_UNDEFINED = 'UNDEFINED';

    /**
     * List of error messages and codes.
     */
    private const MESSAGE_CODES = [
        'Could not find a product with SKU' => self::ERROR_PRODUCT_NOT_FOUND,
        'The required options you selected are not available' => self::ERROR_NOT_SALABLE,
        'Product that you are trying to add is not available.' => self::ERROR_NOT_SALABLE,
        'This product is out of stock' => self::ERROR_INSUFFICIENT_STOCK,
        'There are no source items' => self::ERROR_NOT_SALABLE,
        'The fewest you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The most you may purchase is' => self::ERROR_INSUFFICIENT_STOCK,
        'The requested qty is not available' => self::ERROR_INSUFFICIENT_STOCK,
    ];

    /**
     * @var array
     */
    private array $errors = [];
    private $cart;

    /**
     * InitializeNegotiableQuote constructor.
     *
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param AddProductsToCartOutputFactory $addProductsToCartOutputFactory
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param QuoteFactory $quoteFactory
     * @param FuseBidGraphqlHelper $fuseBidGraphqlHelper
     * @param LoggerInterface $logger
     * @param QuoteIdMask $quoteIdMaskResource
     */
    public function __construct(
        protected GetCartForUser $getCartForUser,
        protected CartRepositoryInterface $cartRepository,
        protected AddProductsToCartOutputFactory $addProductsToCartOutputFactory,
        protected FuseBidGraphqlHelper $fuseBidGraphqlHelper,
        protected QuoteFactory $quoteFactory,
        protected LoggerInterface $logger,
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
        $this->fuseBidGraphqlHelper->validateCartUid($args);
        $maskedCartId = $args['uid'];
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();
        $quoteId = $this->quoteIdMaskResource->getUnmaskedQuoteId($maskedCartId);
        $quote = $this->quoteFactory->create()->load($quoteId);
        $customerId = $quote->getCustomerId();
        $this->logger->info(__METHOD__ . ':' . __LINE__
        .' : Requesting load cart by quote id : '.$quoteId.' and customer id : '.$customerId);
        $cart = $this->getCartForUser->execute($maskedCartId, $customerId, $storeId);
        $this->logger->info(__METHOD__ . ':' . __LINE__
        .' : Cart is loaded with quote id : '.$quoteId.' and customer id : '.$customerId);

        if ($cart->getData('has_error')) {
            $errors = $cart->getErrors();
            foreach ($errors as $error) {
                $this->addError($error->getText());
            }
        }
        $output = $this->addProductsToCartOutputFactory->create([
            'cart' => $this->cartRepository->get($cart->getId()),
            'errors' => $this->errors
        ]);
        $this->cart = $output->getCart();

        return [
            'cart' => [
                'model' => $this->cart,
            ],
            'user_errors' => array_map(
                function (CartError $error) {
                    return [
                        'code' => $error->getCode(),
                        'message' => $error->getMessage(),
                        'path' => [$error->getCartItemPosition()]
                    ];
                },
                $output->getErrors()
            )
        ];
    }

    /**
     * Add order line item error
     *
     * @param string $message
     * @param int $cartItemPosition
     * @return void
     */
    private function addError(string $message, int $cartItemPosition = 0): void
    {
        $this->errors[] = new CartError(
            $message,
            $this->getErrorCode($message),
            $cartItemPosition
        );
    }

    /**
     * Get message error code.
     *
     * @param string $message
     * @return string
     */
    private function getErrorCode(string $message): string
    {
        foreach (self::MESSAGE_CODES as $codeMessage => $code) {
            if (false !== stripos($message, $codeMessage)) {
                return $code;
            }
        }

        /* If no code was matched, return the default one */
        return self::ERROR_UNDEFINED;
    }
}
