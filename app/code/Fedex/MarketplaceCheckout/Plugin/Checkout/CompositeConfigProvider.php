<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceCheckout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
namespace Fedex\MarketplaceCheckout\Plugin\Checkout;

use Exception;
use Fedex\MarketplaceProduct\Api\ShopRepositoryInterface;
use Fedex\MarketplaceProduct\Model\Shop;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\ResourceModel\QuoteItemRetriever;
use Fedex\MarketplaceCheckout\Helper\Data as ToggleHelper;

class CompositeConfigProvider
{
    /**
     * @param QuoteItemRetriever $quoteItemRetriever
     * @param ShopRepositoryInterface $shopRepository
     * @param ToggleHelper $toggleHelper
     */
    public function __construct(
        private readonly QuoteItemRetriever      $quoteItemRetriever,
        private readonly ShopRepositoryInterface $shopRepository,
        private readonly ToggleHelper $toggleHelper
    ) {
    }

    /**
     * Adds quote item data to 3p product
     *
     * @param ConfigProviderInterface $subject
     * @param $result
     * @return mixed
     * @throws NoSuchEntityException
     */
    public function afterGetConfig(
        ConfigProviderInterface $subject,
        $result
    ) {
        $quoteItemQuantity = count($result['quoteItemData']);
        for ($i = 0; $i < $quoteItemQuantity; $i++) {
            $quoteItem = $this->quoteItemRetriever->getById($result['quoteItemData'][$i]['item_id']);
            $additionalData = $quoteItem->getAdditionalData();
            $canEditReorder = false;
            if ($additionalData) {
                try {
                    $shopId = $result['quoteItemData'][$i]['mirakl_shop_id'];
                    if(!$shopId){
                        throw new NoSuchEntityException(new Phrase('Shop ID is not set on the quote item'));
                    }
                    $shop = $this->shopRepository->getById($shopId);
                    $shopInfo = $shop->getShippingRateOption();
                    $sellerAltName = $shop->getSellerAltName();
                    $sellerTooltip = $shop->getTooltip();

                    foreach($shop->getAdditionalInfo()['additional_field_values'] as $additionalInfo){
                        if($additionalInfo['code'] === 'allow-edit-reorder'){
                            $canEditReorder = $additionalInfo['value'] === 'true';
                        }
                    }

                } catch (NoSuchEntityException | Exception $e) {
                    $sellerAltName = $sellerTooltip = Shop::DEFAULT_SELLER_ALT_NAME;
                }
                $additionalData = json_decode($quoteItem->getAdditionalData());
                if (isset($additionalData->isMarketplaceProduct)) {
                    $result['quoteItemData'][$i]['seller_name'] = $sellerAltName;
                    $result['quoteItemData'][$i]['tooltip'] = $sellerTooltip;
                    $result['quoteItemData'][$i]['marketplace_total'] = $additionalData->total;
                    $result['quoteItemData'][$i]['marketplace_unit_price'] = $additionalData->unit_price;
                    $result['quoteItemData'][$i]['marketplace_image'] = $additionalData->image;
                    $result['quoteItemData'][$i]['marketplace_quantity'] = $additionalData->quantity;
                    $result['quoteItemData'][$i]['marketplace_name'] = $additionalData->marketplace_name ?? $additionalData->navitor_name;
                    $result['quoteItemData'][$i]['offer_id'] = $additionalData->offer_id;
                    $result['quoteItemData'][$i]['isMarketplaceProduct'] = $additionalData->isMarketplaceProduct;
                    $result['quoteItemData'][$i]['seller_sku'] =
                        isset($additionalData->seller_sku)??isset($additionalData->navitor_sku)??'';
                    $result['quoteItemData'][$i]['supplierPartAuxiliaryID'] =
                        $additionalData->supplierPartAuxiliaryID;
                    $result['quoteItemData'][$i]['can_edit_reorder'] = $canEditReorder;
                    $result['quoteItemData'][$i]['seller_ship_account_enabled'] = $shopInfo['customer_shipping_account_enabled'] ?? false;

                    $surcharge = 0;
                    if (isset($additionalData->mirakl_shipping_data) &&
                        isset($additionalData->mirakl_shipping_data->surcharge_amount)) {
                        $surcharge = (float) $additionalData->mirakl_shipping_data->surcharge_amount;
                    }
                    $result['quoteItemData'][$i]['surcharge'] = number_format($surcharge, 2);
                }
            }
        }
        return $result;
    }
}
