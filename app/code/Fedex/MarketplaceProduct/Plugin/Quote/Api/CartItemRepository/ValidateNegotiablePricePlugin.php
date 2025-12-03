<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Plugin\Quote\Api\CartItemRepository;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\NegotiableQuote\Model\NegotiableQuoteItemFactory;
use Magento\NegotiableQuote\Model\ResourceModel\NegotiableQuoteItem as NegotiableItemResource;
use Magento\Framework\Exception\InputException;
use Magento\NegotiableQuote\Api\Data\NegotiableQuoteItemInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\ResourceModel\QuoteItemRetriever;

class ValidateNegotiablePricePlugin
{
    /**
     * Constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param NegotiableItemResource $negotiableItemResource
     * @param NegotiableQuoteItemFactory $negotiableQuoteItemFactory
     * @param QuoteItemRetriever $quoteItemRetriever
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly NegotiableItemResource $negotiableItemResource,
        private readonly NegotiableQuoteItemFactory $negotiableQuoteItemFactory,
        private readonly QuoteItemRetriever $quoteItemRetriever
    ) {
    }

    /**
     * Validate negotiable quote item negotiated pricing
     *
     * @param CartItemRepositoryInterface $subject
     * @param CartItemInterface $entity
     * @throws InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        CartItemRepositoryInterface $subject,
        CartItemInterface $entity
    ): void {
        if (!$entity->getExtensionAttributes() || !$entity->getExtensionAttributes()->getNegotiableQuoteItem()) {
            return;
        }
        $oldNegotiableItem = $this->negotiableQuoteItemFactory->create();
        $this->negotiableItemResource->load($oldNegotiableItem, $entity->getItemId());
        $quoteItem = $this->quoteItemRetriever->getById((int)$entity->getItemId());
        try {
            $quote = $quoteItem->getQuote() ?: $this->cartRepository->get($quoteItem->getQuoteId());
        } catch (NoSuchEntityException $e) {
            return;
        }
        $quoteItem->setQuote($quote);
        $negotiableItem = $entity->getExtensionAttributes()->getNegotiableQuoteItem();

        if(!$quoteItem->getMiraklShopId()) {
            $this->validateDiscountValues($negotiableItem, $quoteItem);
        }

        $negotiableItem->setData(array_merge($oldNegotiableItem->getData(), $negotiableItem->getData()));
        if ($negotiableItem->getExtensionAttributes()->getNegotiatedPriceType()) {
            $negotiableItem->setNegotiatedPriceType(
                $negotiableItem->getExtensionAttributes()->getNegotiatedPriceType()
            );
        }
        if ($negotiableItem->getExtensionAttributes()->getNegotiatedPriceValue()) {
            $negotiableItem->setNegotiatedPriceValue(
                $negotiableItem->getExtensionAttributes()->getNegotiatedPriceValue()
            );
        }
        $quoteItem = $quote->getItemById($entity->getItemId());
        $extensionAttributes = $quoteItem->getExtensionAttributes();
        $extensionAttributes->setNegotiableQuoteItem($negotiableItem);
        $quoteItem->setExtensionAttributes($extensionAttributes);
    }

    /**
     * Validate ranges of negotiated_price_value
     *
     * @param NegotiableQuoteItemInterface $negotiableQuoteItem
     * @param CartItemInterface $quoteItem
     * @throws InputException
     */
    private function validateDiscountValues(
        NegotiableQuoteItemInterface $negotiableQuoteItem,
        CartItemInterface $quoteItem
    ): void {
        if (!$negotiableQuoteItem->getExtensionAttributes()) {
            return;
        }
        $negotiatedPriceType = $negotiableQuoteItem->getExtensionAttributes()->getNegotiatedPriceType();
        $negotiatedPriceValue = $negotiableQuoteItem->getExtensionAttributes()->getNegotiatedPriceValue();
        $messages = [];
        if ($negotiatedPriceValue < 0) {
            $messages[] = __('Negotiated Price value should be greater than 0');
        }
        $price =  $quoteItem->getProduct()->getFinalPrice();
        $messages = array_merge(
            $messages,
            $this->validateNegotiatedPrice($negotiatedPriceType, $negotiatedPriceValue, $price)
        );

        if (!count($messages)) {
            return;
        }
        $exception = new InputException();
        foreach ($messages as $message) {
            $exception->addError($message);
        }
        throw $exception;
    }

    /**
     * Validate Negotiated Price value for negotiated price type
     *
     * @param int $negotiatedPriceType
     * @param float $negotiatedPriceValue
     * @param float $productPrice
     * @return array
     */
    private function validateNegotiatedPrice(
        int $negotiatedPriceType,
        float $negotiatedPriceValue,
        float $productPrice
    ): array {
        $messages = [];
        switch ($negotiatedPriceType) {
            case NegotiableQuoteItemInterface::NEGOTIATED_PRICE_TYPE_PERCENTAGE_DISCOUNT:
                if ($negotiatedPriceValue < 0 || $negotiatedPriceValue > 100) {
                    $messages[] = __('Discount Percentage value should be > 0 and less than 100');
                }
                break;
            case NegotiableQuoteItemInterface::NEGOTIATED_PRICE_TYPE_AMOUNT_DISCOUNT:
                if ($negotiatedPriceValue > $productPrice) {
                    $messages[] = __('Discount Amount cannot be greater than item price');
                }
                break;
            case NegotiableQuoteItemInterface::NEGOTIATED_PRICE_TYPE_PROPOSED_TOTAL:
                if ($negotiatedPriceValue > $productPrice) {
                    $messages[] = __('Proposed price cannot be greater than item price');
                }
                break;
        }

        return $messages;
    }
}
