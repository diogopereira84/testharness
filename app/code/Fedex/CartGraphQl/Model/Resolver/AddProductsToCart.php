<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Model\Quote\IntegrationItem\Repository as IntegrationItemRepository;
use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Quote\Model\Cart\Data\AddProductsToCartOutputFactory;
use Magento\Quote\Model\Cart\Data\Error;
use Magento\Quote\Model\Cart\Data\Error as CartError;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Quote\Api\CartRepositoryInterface;
use Exception;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\CartGraphQl\Model\Address\CollectRates\ShippingRate;
use Fedex\Cart\Api\CartIntegrationRepositoryInterface;

class AddProductsToCart extends AbstractResolver
{
    /**#@+
     * Error message codes
     */
    private const ERROR_PRODUCT_NOT_FOUND = 'PRODUCT_NOT_FOUND';
    private const ERROR_INSUFFICIENT_STOCK = 'INSUFFICIENT_STOCK';
    private const ERROR_NOT_SALABLE = 'NOT_SALABLE';
    private const ERROR_UNDEFINED = 'UNDEFINED';
    /**#@-*/

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

    /**
     * @var
     */
    private $cart;

    /**
     * @param GetCartForUser $getCartForUser
     * @param QuoteProductAdd $quoteProductAdd
     * @param IntegrationItemRepository $integrationItemRepository
     * @param CartRepositoryInterface $cartRepository
     * @param AddProductsToCartOutputFactory $addProductsToCartOutputFactory
     * @param ShippingRate $rate
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param RequestCommandFactory $requestCommandFactory
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param ValidationComposite $validationComposite
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly QuoteProductAdd $quoteProductAdd,
        private readonly IntegrationItemRepository $integrationItemRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly AddProductsToCartOutputFactory $addProductsToCartOutputFactory,
        private readonly ShippingRate $rate,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        RequestCommandFactory $requestCommandFactory,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
        ValidationComposite $validationComposite,
        NewRelicHeaders $newRelicHeaders,
        array $validations = []
    ) {
        parent::__construct(
            $requestCommandFactory,
            $batchResponseFactory,
            $loggerHelper,
            $validationComposite,
            $newRelicHeaders,
            $validations
        );
    }

    /**
     * @param ContextInterface $context
     * @param Field $field
     * @param array $requests
     * @param array $headerArray
     * @return BatchResponse
     * @throws GraphQlInStoreException
     */
    public function proceed(
        ContextInterface $context,
        Field $field,
        array $requests,
        array $headerArray
    ): BatchResponse {
        try {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL start: ' . __CLASS__, $headerArray);
            foreach ($requests as $request) {
                $args = $request->getArgs();
                $maskedCartId = $args['cartId'];
                $cartItemsData = $args['cartItems'];
                $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

                // Shopping Cart validation
                $cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
                $this->quoteProductAdd->setCart($cart);

                $this->setShippingData($cart);

                foreach ($cartItemsData as $cartItemData) {
                    $data = json_decode($cartItemData['data'], true);

                    if (empty($data)) {
                        throw new GraphQlInStoreException('Invalid cart item data');
                    }

                    $this->quoteProductAdd->addItemToCart($cartItemData['data']);
                    $instanceId = $data['fxoProductInstance']['productConfig']['product']['instanceId'] ?? null;
                    $itemId = $this->quoteProductAdd->findCartItemByInstanceIdExternal($instanceId);
                    if ($itemId) {
                        $this->integrationItemRepository->saveByQuoteItemId((int)$itemId, $cartItemData['data']);
                    }
                }

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
                $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);

                $returnData = [
                    'cart' => [
                        'model' => $this->cart,
                    ],
                    'user_errors' => array_map(
                        function (Error $error) {
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
            $response = $this->batchResponseFactory->create();

            foreach ($requests as $request) {
                $response->addResponse($request, $returnData ?? null);
            }
            return $response;
        } catch (Exception $e) {
            $this->loggerHelper->error(__CLASS__ . __METHOD__ . ':' . __LINE__ . ' Error on saving information into cart. ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error(__CLASS__ . __METHOD__ . ':' . __LINE__ . $e->getTraceAsString(), $headerArray);
            $this->loggerHelper->error('GTN: '. $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInStoreException($e->getMessage());
        }
    }

    /**
     * @param $cart
     * @return void
     */
    private function setShippingData($cart): void
    {
        $shippingAddress = $cart->getShippingAddress();
        $integration = $this->cartIntegrationRepository->getByQuoteId($cart->getId());
        if ((!empty($shippingAddress)) && (!empty($shippingAddress->getCountryId()))) {
            $this->rate->collect($shippingAddress, $integration);
            $this->cartRepository->save($cart);
        }
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
        $this->errors[] = new CartError (
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
