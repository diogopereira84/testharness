<?php
/**
 * @category    Fedex
 * @package     Fedex_CartGraphQl
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Olimjon Akhmedov <oakhmedov@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Resolver\AbstractResolver;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Fedex\InStoreConfigurations\Model\System\Config as InstoreConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\QuoteGraphQl\Model\CartItem\DataProvider\UpdateCartItems as  UpdateCartItemsProvider;
use Fedex\Cart\Model\Quote\IntegrationItem\Repository as IntegrationItemRepository;
use Fedex\CartGraphQl\Model\Address\Builder;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

/**
 * @inheritdoc
 */
class UpdateCartItems extends AbstractResolver
{
    protected $cart;

    /**
     * @param GetCartForUser $getCartForUser
     * @param CartRepositoryInterface $cartRepository
     * @param CartIntegrationRepositoryInterface $cartIntegrationRepository
     * @param UpdateCartItemsProvider $updateCartItems
     * @param QuoteProductAdd $quoteProductAdd
     * @param IntegrationItemRepository $integrationItemRepository
     * @param Builder $builder
     * @param InstoreConfig $instoreConfig
     * @param RequestCommandFactory $requestCommandFactory
     * @param ValidationBatchComposite $validationComposite
     * @param BatchResponseFactory $batchResponseFactory
     * @param LoggerHelper $loggerHelper
     * @param NewRelicHeaders $newRelicHeaders
     * @param array $validations
     */
    public function __construct(
        private readonly GetCartForUser $getCartForUser,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartIntegrationRepositoryInterface $cartIntegrationRepository,
        private readonly UpdateCartItemsProvider  $updateCartItems,
        private readonly QuoteProductAdd $quoteProductAdd,
        private readonly IntegrationItemRepository $integrationItemRepository,
        private readonly Builder $builder,
        private readonly InstoreConfig $instoreConfig,
        RequestCommandFactory $requestCommandFactory,
        ValidationBatchComposite $validationComposite,
        BatchResponseFactory $batchResponseFactory,
        LoggerHelper $loggerHelper,
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
     * @throws GraphQlInputException
     * @throws GraphQlNoSuchEntityException
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
                $this->cart = $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
                $this->quoteProductAdd->setCart($this->cart);

                $qtyItems = null;
                foreach ($cartItemsData as $cartItemData) {
                    if (isset($cartItemData['data'])) {
                        $this->quoteProductAdd->addItemToCart($cartItemData['data']);
                        $data = json_decode($cartItemData['data'], true);
                        $instanceId = $data['fxoProductInstance']['productConfig']['product']['instanceId'];
                        $itemId = $this->quoteProductAdd->findCartItemByInstanceIdExternal($instanceId);
                        if ($itemId) {
                            $this->integrationItemRepository->saveByQuoteItemId((int)$itemId, $cartItemData['data']);
                        }
                    } elseif (isset($cartItemData['quantity']) && isset($cartItemData['cart_item_id'])) {
                        $qtyItems[] = $cartItemData;
                    }
                }

                if ($qtyItems) {
                    $this->updateCartItems->processCartItems($this->cart, $qtyItems);
                }

                $shippingAddress = $this->cart->getShippingAddress();
                if ((!empty($shippingAddress)) && (!empty($shippingAddress->getCountryId()))) {
                    $this->builder->setShippingData($this->cart, $shippingAddress);
                }

                $this->cartRepository->save($this->cart);
                $this->cart = $this->cartRepository->get($this->cart->getId());
                if (empty($this->cart->getItems()) && $this->instoreConfig->isEnableEstimatedSubtotalFix()) {
                    $this->resetQuoteIntegrationTotals((string) $this->cart->getId());
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->loggerHelper->error(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' ' . $e->getMessage(), $headerArray);
            $this->loggerHelper->error('GTN: ' . $this->cart?->getGtn(), $headerArray);
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        $this->loggerHelper->info(__METHOD__ . ':' . __LINE__ . ' Magento graphQL end: ' . __CLASS__, $headerArray);
        $response = $this->batchResponseFactory->create();
        foreach ($requests as $request) {
            $response->addResponse(
                $request,
                [
                    'cart' => [
                        'model' => $this->cart
                    ]
                ]
            );
        }
        return $response;
    }

    /**
     * @param string $quoteId
     * @return void
     */
    private function resetQuoteIntegrationTotals(string $quoteId): void
    {
        $quoteIntegration = $this->cartIntegrationRepository->getByQuoteId($quoteId);
        $quoteIntegration->setRaqNetAmount(0);
        $this->cartIntegrationRepository->save($quoteIntegration);
    }
}
