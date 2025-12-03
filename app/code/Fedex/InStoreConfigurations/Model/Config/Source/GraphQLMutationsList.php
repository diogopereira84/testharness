<?php
/**
 * @category  Fedex
 * @package   Fedex_InstoreConfigurations
 * @copyright Copyright (c) 2025 FedEx.
 * @author    Pedro Basseto <pedro.basseto.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\InStoreConfigurations\Model\Config\Source;

class GraphQLMutationsList implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'addOrUpdateDueDate', 'label' => __('AddOrUpdateDueDate')],
            ['value' => 'addOrUpdateFedexAccountNumber', 'label' => __('AddOrUpdateFedexAccountNumber')],
            ['value' => 'addProductsToCart', 'label' => __('AddProductsToCart')],
            ['value' => 'createOrUpdateOrder', 'label' => __('CreateOrUpdateOrder')],
            ['value' => 'notes', 'label' => __('Notes')],
            ['value' => 'placeOrder', 'label' => __('PlaceOrder')],
            ['value' => 'updateCartItems', 'label' => __('UpdateCartItems')],
            ['value' => 'updateGuestCartContactInformation', 'label' => __('UpdateGuestCartContactInformation')],
            ['value' => 'updateOrderDelivery', 'label' => __('UpdateOrderDelivery')],
            ['value' => 'createEmptyCart', 'label' => __('CreateEmptyCart')],
            ['value' => 'cart', 'label' => __('Cart')],
            ['value' => 'products', 'label' => __('GetProducts')],
            ['value' => 'getQuoteDetails', 'label' => __('GetQuoteDetails')],
            ['value' => 'updateNegotiableQuote', 'label' => __('UpdateNegotiableQuote')],
            ['value' => 'lateOrder', 'label' => __('LateOrder')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'addOrUpdateDueDate' => __('AddOrUpdateDueDate'),
            'addOrUpdateFedexAccountNumber' => __('AddOrUpdateFedexAccountNumber'),
            'addProductsToCart' => __('AddProductsToCart'),
            'createOrUpdateOrder' => __('CreateOrUpdateOrder'),
            'notes' => __('Notes'),
            'placeOrder' => __('PlaceOrder'),
            'updateCartItems' => __('UpdateCartItems'),
            'updateGuestCartContactInformation' => __('UpdateGuestCartContactInformation'),
            'updateOrderDelivery' => __('UpdateOrderDelivery'),
            'createEmptyCart' => __('CreateEmptyCart'),
            'cart' => __('Cart'),
            'products' => __('Products'),
            'getQuoteDetails' => __('GetQuoteDetails'),
            'updateNegotiableQuote' => __('UpdateNegotiableQuote'),
            'lateOrder' => __('LateOrder')
        ];
    }
}



